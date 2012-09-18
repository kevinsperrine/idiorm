<?php
/**
 * Idiorm
 *
 * The same functionality exists, but this is a massive (backwards incompatible)
 * refactoring to match PSR standards (checked with PHP Code Sniffer).
 *
 * https://github.com/kevinsperrine/idiorm
 * 
 * forked from 
 * 
 * http://github.com/j4mie/idiorm/
 *
 * A single-class super-simple database abstraction layer for PHP.
 * Provides (nearly) zero-configuration object-relational mapping
 * and a fluent interface for building basic, commonly-used queries.
 *
 * BSD Licensed.
 *
 * Copyright (c) 2010, Jamie Matthews
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category ORM
 * @package  Idiorm
 * @author   Jamie Matthews <jamie.matthews@gmail.com>
 * @author   Kevin Perrine <kperrine@gmail.com>
 * @license  BSD http://opensource.org/licenses/bsd-license.php
 * @version  1.1.1
 * @link     http://github.com/j4mie/idiorm/
 * @link     https://github.com/kevinsperrine/idiorm
 */

/**
 * Idiorm
 *
 * http://github.com/j4mie/idiorm/
 *
 * A single-class super-simple database abstraction layer for PHP.
 * Provides (nearly) zero-configuration object-relational mapping
 * and a fluent interface for building basic, commonly-used queries.
 *
 * BSD Licensed.
 *
 * Copyright (c) 2010, Jamie Matthews
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category ORM
 * @package  Idiorm
 * @author   Jamie Matthews <jamie.matthews@gmail.com>
 * @author   Kevin Perrine <kperrine@gmail.com>
 * @license  BSD http://opensource.org/licenses/bsd-license.php
 * @version  1.1.1
 * @link     https://github.com/kevinsperrine/idiorm
 */
class Idiorm
{

    // ----------------------- //
    // --- CLASS CONSTANTS --- //
    // ----------------------- //

    // Where condition array keys
    const WHERE_FRAGMENT = 0;
    const WHERE_VALUES = 1;

    // ------------------------ //
    // --- CLASS PROPERTIES --- //
    // ------------------------ //

    // Class configuration
    protected static $config = array(
        'connection_string' => 'sqlite::memory:',
        'id_column' => 'id',
        'id_column_overrides' => array(),
        'error_mode' => PDO::ERRMODE_EXCEPTION,
        'username' => null,
        'password' => null,
        'driver_options' => null,
        'identifier_quote_character' => null, // if this is null, will be autodetected
        'logging' => false,
        'caching' => false,
    );

    // Database connection, instance of the PDO class
    protected static $database;

    // Last query run, only populated if logging is enabled
    protected static $last_query;

    // Log of all queries run, only populated if logging is enabled
    protected static $query_log = array();

    // Query cache, only used if query caching is enabled
    protected static $query_cache = array();

    // --------------------------- //
    // --- INSTANCE PROPERTIES --- //
    // --------------------------- //

    // The name of the table the current ORM instance is associated with
    protected $table_name;

    // Alias for the table to be used in SELECT queries
    protected $tableAlias = null;

    // Values to be bound to the query
    protected $values = array();

    // Columns to select in the result
    protected $result_columns = array('*');

    // Are we using the default result column or have these been manually changed?
    protected $using_default_result_columns = true;

    // Join sources
    protected $join_sources = array();

    // Should the query include a DISTINCT keyword?
    protected $distinct = false;

    // Is this a raw query?
    protected $is_raw_query = false;

    // The raw query
    protected $rawQuery = '';

    // The raw query parameters
    protected $raw_parameters = array();

    // Array of WHERE clauses
    protected $where_conditions = array();

    // LIMIT
    protected $limit = null;

    // OFFSET
    protected $offset = null;

    // ORDER BY
    protected $order_by = array();

    // GROUP BY
    protected $groupBy = array();

    // The data for a hydrated instance of the class
    protected $data = array();

    // Fields that have been modified during the
    // lifetime of the object
    protected $dirty_fields = array();

    // Is this a new object (has create() been called)?
    protected $is_new = false;

    // Name of the column to use as the primary key for
    // this instance only. Overrides the config settings.
    protected $instance_id_column = null;

    // ---------------------- //
    // --- STATIC METHODS --- //
    // ---------------------- //
    
    /**
     * Pass configuration settings to the class in the form of
     * key/value pairs. As a shortcut, if the second argument
     * is omitted, the setting is assumed to be the DSN string
     * used by PDO to connect to the database. Often, this
     * will be the only configuration required to use Idiorm.
     *
     * @param string $key   configuration key
     * @param string $value configuration value
     *
     * @return none
     */
    public static function configure($key, $value=null)
    {
        // Shortcut: If only one argument is passed, 
        // assume it's a connection string
        if (is_null($value)) {
            $value = $key;
            $key = 'connection_string';
        }
        self::$config[$key] = $value;
    }
    
    /**
     * Despite its slightly odd name, this is actually the factory
     * method used to acquire instances of the class. It is named
     * this way for the sake of a readable interface, ie
     * ORM::forTable('table_name')->findOne()-> etc. As such,
     * this will normally be the first method called in a chain.
     *
     * @param string $table_name table name
     *
     * @return Idiorm new IdiormObject for table
     */
    public static function forTable($table_name)
    {
        self::setupDatabase();
        return new self($table_name);
    }
    
    /**
     * Set up the database connection used by the class.
     *
     * @return none
     */
    protected static function setupDatabase()
    {
        if (!is_object(self::$database)) {
            $connection_string = self::$config['connection_string'];
            $username = self::$config['username'];
            $password = self::$config['password'];
            $driver_options = self::$config['driver_options'];
            $database = new PDO($connection_string, $username, $password, $driver_options);
            $database->setAttribute(PDO::ATTR_ERRMODE, self::$config['error_mode']);
            self::setDatabase($database);
        }
    }

    /**
     * Set the PDO object used by Idiorm to communicate with the database.
     * This is public in case the ORM should use a ready-instantiated
     * PDO object as its database connection.
     *
     * @param PDO $pdoDatabase The PDO object used for DB access
     *
     * @return none
     */
    public static function setDatabase($pdoDatabase)
    {
        self::$database = $pdoDatabase;
        self::setupIdentifierQuoteCharacter();
    }

    /**
     * Detect and initialise the character used to quote identifiers
     * (table names, column names etc). If this has been specified
     * manually using ORM::configure('identifier_quote_character', 'some-char'),
     * this will do nothing.
     *
     * @return none
     */
    public static function setupIdentifierQuoteCharacter()
    {
        if (is_null(self::$config['identifier_quote_character'])) {
            self::$config['identifier_quote_character'] = self::detectIdentifierQuoteCharacter();
        }
    }
    
    /**
     * Return the correct character used to quote identifiers (table
     * names, column names etc) by looking at the driver being used by PDO.
     *
     * @return string the quote character used by the pdo driver
     */
    protected static function detectIdentifierQuoteCharacter()
    {
        switch(self::$database->getAttribute(PDO::ATTR_DRIVER_NAME)) {
        case 'pgsql':
        case 'sqlsrv':
        case 'dblib':
        case 'mssql':
        case 'sybase':
            return '"';
        
        case 'mysql':
        case 'sqlite':
        case 'sqlite2':

        default:
            return '`';
        }
    }

    /**
     * Returns the PDO instance used by the the ORM to communicate with
     * the database. This can be called if any low-level DB access is
     * required outside the class.
     *
     * @return PDO the current pdo instance
     */
    public static function getDatabase()
    {
        self::setupDatabase(); // required in case this is called before Idiorm is instantiated
        return self::$database;
    }

    /**
     * Add a query to the internal query log. Only works if the
     * 'logging' config option is set to true.
     *
     * This works by manually binding the parameters to the query - the
     * query isn't executed like this (PDO normally passes the query and
     * parameters to the database which takes care of the binding) but
     * doing it this way makes the logged queries more readable.
     *
     * @param string $query      PDO Query string
     * @param mixed  $parameters array or string of parameters to late bind
     *
     * @return boolean true if logging is enabled, false otherwise
     */
    protected static function logQuery($query, $parameters)
    {
        // If logging is not enabled, do nothing
        if (!self::$config['logging']) {
            return false;
        }

        if (count($parameters) > 0) {
            // Escape the parameters
            $parameters = array_map(array(self::$database, 'quote'), $parameters);

            // Replace placeholders in the query for vsprintf
            $query = str_replace("?", "%s", $query);

            // Replace the question marks in the query with the parameters
            $bound_query = vsprintf($query, $parameters);
        } else {
            $bound_query = $query;
        }

        self::$last_query = $bound_query;
        self::$query_log[] = $bound_query;
        return true;
    }

    /**
     * Get the last query executed. Only works if the
     * 'logging' config option is set to true. Otherwise
     * this will return null.
     *
     * @return string the last query executed
     */
    public static function getLastQuery()
    {
        return self::$last_query;
    }

    /**
     * Get an array containing all the queries run up to
     * now. Only works if the 'logging' config option is
     * set to true. Otherwise returned array will be empty.
     *
     * @return array an array of all logged queries
     */
    public static function getQueryLog()
    {
        return self::$query_log;
    }



    // ------------------------ //
    // --- INSTANCE METHODS --- //
    // ------------------------ //

    /**
     * "Private" constructor; shouldn't be called directly.
     * Use the ORM::forTable factory method instead.
     *
     * @param string $table_name table name
     * @param array  $data       data used to create the object
     */
    protected function __construct($table_name, $data = array())
    {
        $this->table_name = $table_name;
        $this->data = $data;
    }

    /**
     * Create a new, empty instance of the class. Used
     * to add a new row to your database. May optionally
     * be passed an associative array of data to populate
     * the instance. If so, all fields will be flagged as
     * dirty so all will be saved to the database when
     * save() is called.
     *
     * @param array $data data used to create the object
     *
     * @return Idiorm return current idiorm object for chaining
     */
    public function create($data = null)
    {
        $this->is_new = true;
        if (!is_null($data)) {
            return $this->hydrate($data)->forceAllDirty();
        }
        return $this;
    }

    /**
     * Specify the ID column to use for this instance or array of instances only.
     * This overrides the id_column and id_column_overrides settings.
     *
     * This is mostly useful for libraries built on top of Idiorm, and will
     * not normally be used in manually built queries. If you don't know why
     * you would want to use this, you should probably just ignore it.
     *
     * @param string $id_column name of the id column used to override the default
     *
     * @return Idiorm current idiorm object
     */
    public function useIdColumn($id_column)
    {
        $this->instance_id_column = $id_column;
        return $this;
    }

    /**
     * Create an ORM instance from the given row (an associative
     * array of data fetched from the database)
     *
     * @param array $row associative array of values
     *
     * @return Idiorm idiorm instance created from data
     */
    protected function createInstanceFromRow($row)
    {
        $instance = self::forTable($this->table_name);
        $instance->useIdColumn($this->instance_id_column);
        $instance->hydrate($row);
        return $instance;
    }

    /**
     * Tell the ORM that you are expecting a single result
     * back from your query, and execute it. Will return
     * a single instance of the ORM class, or false if no
     * rows were returned.
     * As a shortcut, you may supply an ID as a parameter
     * to this method. This will perform a primary key
     * lookup on the table.
     *
     * @param id $id id of object to find
     *
     * @return mixed Idiorm instance if ID found, false otherwise
     */
    public function findOne($id = null)
    {
        if (!is_null($id)) {
            $this->whereIdIs($id);
        }
        $this->limit(1);
        $rows = $this->run();

        if (empty($rows)) {
            return false;
        }

        return $this->createInstanceFromRow($rows[0]);
    }

    /**
     * Tell the ORM that you are expecting multiple results
     * from your query, and execute it. Will return an array
     * of instances of the ORM class, or an empty array if
     * no rows were returned.
     *
     * @return array array of Idiorm instance or empty array
     */
    public function findMany()
    {
        $rows = $this->run();
        return array_map(array($this, 'createInstanceFromRow'), $rows);
    }

    /**
     * Tell the ORM that you wish to execute a COUNT query.
     * Will return an integer representing the number of
     * rows returned.
     *
     * @return integer count of results found
     */
    public function count()
    {
        $this->selectExpression('COUNT(*)', 'count');
        $result = $this->findOne();
        return ($result !== false && isset($result->count)) ? (int) $result->count : 0;
    }

    /**
     * This method can be called to hydrate (populate) this
     * instance of the class from an associative array of data.
     * This will usually be called only from inside the class,
     * but it's public in case you need to call it directly.
     *
     * @param array $data populate the current instance with the supplied data
     *
     * @return Idiorm current instance for chaining
     */
    public function hydrate($data = array())
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Force the ORM to flag all the fields in the $data array
     * as "dirty" and therefore update them when save() is called.
     *
     * @return Idiorm current idiorm instance
     */
    public function forceAllDirty()
    {
        $this->dirty_fields = $this->data;
        return $this;
    }

    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query. If this method
     * is called, all other query building methods will be ignored.
     *
     * @param string $query      query string
     * @param array  $parameters array of parameters to bind to the query
     *
     * @return Idiorm current instance
     *
     * @link https://github.com/j4mie/idiorm/commit/f9af1ffce3b01e6e87ded22cc5903c0bf253fbc1
     */
    public function rawQuery($query, $parameters = array())
    {
        $this->is_raw_query = true;
        $this->rawQuery = $query;
        $this->raw_parameters = $parameters;
        return $this;
    }

    /**
     * Add an alias for the main table to be used in SELECT queries
     *
     * @param string $alias table alias
     *
     * @return Idiorm current instance
     */
    public function tableAlias($alias)
    {
        $this->tableAlias = $alias;
        return $this;
    }

    /**
     * Internal method to add an unquoted expression to the set
     * of columns returned by the SELECT query. The second optional
     * argument is the alias to return the expression as.
     *
     * @param string $expr  unquoted expression to run
     * @param string $alias alias to return the expression as
     *
     * @return Idiorm current instance
     */
    protected function addResultColumn($expr, $alias = null)
    {
        if (!is_null($alias)) {
            $expr .= " AS " . $this->quoteIdentifier($alias);
        }

        if ($this->using_default_result_columns) {
            $this->result_columns = array($expr);
            $this->using_default_result_columns = false;
        } else {
            $this->result_columns[] = $expr;
        }
        return $this;
    }

    /**
     * Add a column to the list of columns returned by the SELECT
     * query. This defaults to '*'. The second optional argument is
     * the alias to return the column as.
     *
     * @param string $column column
     * @param string $alias  alias for return column
     *
     * @return Idiorm current instance
     */
    public function select($column, $alias = null)
    {
        $column = $this->quoteIdentifier($column);
        return $this->addResultColumn($column, $alias);
    }

    /**
     * Add an unquoted expression to the list of columns returned
     * by the SELECT query. The second optional argument is
     * the alias to return the column as.
     *
     * @param string $expr  unquoted expression
     * @param string $alias alias to return the column as
     *
     * @return Idiorm current instance
     */
    public function selectExpression($expr, $alias = null)
    {
        return $this->addResultColumn($expr, $alias);
    }

    /**
     * Add a DISTINCT keyword before the list of columns in the SELECT query
     *
     * @return Idiorm current instance
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Internal method to add a JOIN source to the query.
     *
     * The join_operator should be one of INNER, LEFT OUTER, CROSS etc - this
     * will be prepended to JOIN.
     *
     * The table should be the name of the table to join to.
     *
     * The constraint may be either a string or an array with three elements. If it
     * is a string, it will be compiled into the query as-is, with no escaping. The
     * recommended way to supply the constraint is as an array with three elements:
     *
     * first_column, operator, second_column
     *
     * Example: array('user.id', '=', 'profile.user_id')
     *
     * will compile to
     *
     * ON `user`.`id` = `profile`.`user_id`
     *
     * The final (optional) argument specifies an alias for the joined table.
     *
     * @param string $join_operator type of join (inner, left, cross, etc)
     * @param string $table         table with which to join
     * @param mixed  $constraint    string or array used to constrain the join
     * @param string $tableAlias    string to alias the joined table
     *
     * @return Idiorm current instance
     */
    protected function addJoinSource($join_operator, $table, $constraint, $tableAlias = null)
    {

        $join_operator = trim("{$join_operator} JOIN");

        $table = $this->quoteIdentifier($table);

        // Add table alias if present
        if (!is_null($tableAlias)) {
            $tableAlias = $this->quoteIdentifier($tableAlias);
            $table .= " {$tableAlias}";
        }

        // Build the constraint
        if (is_array($constraint)) {
            list($first_column, $operator, $second_column) = $constraint;
            $first_column = $this->quoteIdentifier($first_column);
            $second_column = $this->quoteIdentifier($second_column);
            $constraint = "{$first_column} {$operator} {$second_column}";
        }

        $this->join_sources[] = "{$join_operator} {$table} ON {$constraint}";
        return $this;
    }

    /**
     * Add a simple JOIN source to the query
     *
     * @param string $table      table with which to join
     * @param mixed  $constraint string or array to constrain the join
     * @param string $tableAlias alias to return the joined result
     *
     * @return Idiorm current instance
     */
    public function join($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("", $table, $constraint, $tableAlias);
    }

    /**
     * Add an INNER JOIN souce to the query
     *
     * @param string $table      table with which to join
     * @param mixed  $constraint string or array to constrain the join
     * @param string $tableAlias alias for returned result
     *
     * @return Idiorm current instance
     */
    public function innerJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("INNER", $table, $constraint, $tableAlias);
    }

    /**
     * Add a LEFT OUTER JOIN souce to the query
     * 
     * @param string $table      table with which to join
     * @param mixed  $constraint string or array to constrain the join
     * @param string $tableAlias alias for returned result
     *
     * @return Idiorm current instance
     */
    public function leftOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("LEFT OUTER", $table, $constraint, $tableAlias);
    }

    /**
     * Add an RIGHT OUTER JOIN souce to the query
     * 
     * @param string $table      table with which to join
     * @param mixed  $constraint string or array to constrain the join
     * @param string $tableAlias alias for returned result
     *
     * @return Idiorm current instance
     */
    public function rightOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("RIGHT OUTER", $table, $constraint, $tableAlias);
    }

    /**
     * Add an FULL OUTER JOIN souce to the query
     * 
     * @param string $table      table with which to join
     * @param mixed  $constraint string or array to constrain the join
     * @param string $tableAlias alias for returned result
     *
     * @return Idiorm current instance
     */
    public function fullOuterJoin($table, $constraint, $tableAlias = null)
    {
        return $this->addJoinSource("FULL OUTER", $table, $constraint, $tableAlias);
    }

    /**
     * Internal method to add a WHERE condition to the query
     *
     * @param string $fragment string fragment of where clause
     * @param array  $values   array of values to match to the clause fragments
     *
     * @return Idiorm current instance
     */
    protected function addWhere($fragment, $values = array())
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        $this->where_conditions[] = array(
            self::WHERE_FRAGMENT => $fragment,
            self::WHERE_VALUES => $values,
        );
        return $this;
    }

    /**
     * Helper method to compile a simple COLUMN SEPARATOR VALUE
     * style WHERE condition into a string and value ready to
     * be passed to the addWhere method. Avoids duplication
     * of the call to quoteIdentifier
     *
     * @param string $column_name column name
     * @param string $separator   column separator
     * @param string $value       search value
     *
     * @return Idiorm current instance
     */
    protected function addSimpleWhere($column_name, $separator, $value)
    {
        $column_name = $this->quoteIdentifier($column_name);
        return $this->addWhere("{$column_name} {$separator} ?", $value);
    }

    /**
     * Return a string containing the given number of question marks,
     * separated by commas. Eg "?, ?, ?"
     *
     * @param integer $numPlaceholders number of placeholders to add
     *
     * @return string string of comma separated question marks for placeholders
     */
    protected function createPlaceholders($numPlaceholders)
    {
        return join(", ", array_fill(0, $numPlaceholders, "?"));
    }

    /**
     * Add a WHERE column = value clause to your query. Each time
     * this is called in the chain, an additional WHERE will be
     * added, and these will be ANDed together when the final query
     * is built.
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function where($column_name, $value)
    {
        return $this->whereEqual($column_name, $value);
    }

    /**
     * More explicitly named version of for the where() method.
     * Can be used if preferred.
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereEqual($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, '=', $value);
    }

    /**
     * Add a WHERE column != value clause to your query.
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereNotEqual($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, '!=', $value);
    }

    /**
     * Special method to query the table by its primary key
     *
     * @param string $id id to find
     *
     * @return Idiorm current instance
     */ 
    public function whereIdIs($id)
    {
        return $this->where($this->getIdColumnName(), $id);
    }

    /**
     * Add a WHERE ... LIKE clause to your query.
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereLike($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, 'LIKE', $value);
    }

    /**
     * Add where WHERE ... NOT LIKE clause to your query.
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereNotLike($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, 'NOT LIKE', $value);
    }

    /**
     * Add a WHERE ... > clause to your query
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereGt($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, '>', $value);
    }

    /**
     * Add a WHERE ... < clause to your query
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereLt($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, '<', $value);
    }

    /**
     * Add a WHERE ... >= clause to your query
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereGte($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, '>=', $value);
    }

    /**
     * Add a WHERE ... <= clause to your query
     *
     * @param string $column_name column name
     * @param string $value       value to find
     *
     * @return Idiorm current instance
     */ 
    public function whereLte($column_name, $value)
    {
        return $this->addSimpleWhere($column_name, '<=', $value);
    }

    /**
     * Add a WHERE ... IN clause to your query
     *
     * @param string $column_name column name
     * @param array  $values      values to compare against
     *
     * @return Idiorm current instance
     */ 
    public function whereIn($column_name, $values)
    {
        $column_name = $this->quoteIdentifier($column_name);
        $placeholders = $this->createPlaceholders(count($values));
        return $this->addWhere("{$column_name} IN ({$placeholders})", $values);
    }

    /**
     * Add a WHERE ... NOT IN clause to your query
     *
     * @param string $column_name column name
     * @param array  $values      values to compare against
     *
     * @return Idiorm current instance
     */ 
    public function whereNotIn($column_name, $values)
    {
        $column_name = $this->quoteIdentifier($column_name);
        $placeholders = $this->createPlaceholders(count($values));
        return $this->addWhere("{$column_name} NOT IN ({$placeholders})", $values);
    }

    /**
     * Add a WHERE column IS NULL clause to your query
     *
     * @param string $column_name column name
     *
     * @return Idiorm current instance
     */ 
    public function whereNull($column_name)
    {
        $column_name = $this->quoteIdentifier($column_name);
        return $this->addWhere("{$column_name} IS NULL");
    }

    /**
     * Add a WHERE column IS NOT NULL clause to your query
     *
     * @param string $column_name column name
     *
     * @return Idiorm current instance
     */ 
    public function whereNotNull($column_name)
    {
        $column_name = $this->quoteIdentifier($column_name);
        return $this->addWhere("{$column_name} IS NOT NULL");
    }

    /**
     * Add a raw WHERE clause to the query. The clause should
     * contain question mark placeholders, which will be bound
     * to the parameters supplied in the second argument.
     *
     * @param string $clause     raw clause to use in where statement
     * @param array  $parameters values to bind to statement
     *
     * @return Idiorm current instance
     */ 
    public function whereRaw($clause, $parameters = array())
    {
        return $this->addWhere($clause, $parameters);
    }

    /**
     * Add a LIMIT to the query
     *
     * @param integer $limit limit on return values
     *
     * @return Idiorm current instance
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Add an OFFSET to the query
     *
     * @param integer $offset offset to add to the query
     *
     * @return Idiorm current instance
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Add an ORDER BY clause to the query
     *
     * @param string $column_name column name to order by
     * @param string $ordering    how to order (DESC, ASC)
     *
     * @return Idiorm current instance
     */
    protected function addOrderBy($column_name, $ordering)
    {
        $column_name = $this->quoteIdentifier($column_name);
        $this->order_by[] = "{$column_name} {$ordering}";
        return $this;
    }

    /**
     * Add an ORDER BY column DESC clause
     *
     * @param string $column_name column name to order by
     *
     * @return Idiorm current instance
     */
    public function orderByDesc($column_name)
    {
        return $this->addOrderBy($column_name, 'DESC');
    }

    /**
     * Add an ORDER BY column ASC clause
     *
     * @param string $column_name column name to order by
     *
     * @return Idiorm current instance
     */
    public function orderByAsc($column_name)
    {
        return $this->addOrderBy($column_name, 'ASC');
    }

    /**
     * Add a raw ORDER BY clause
     * 
     * @param string $clause raw clause to use for ordering
     *
     * @return Idiorm current instance
     *
     * @link https://github.com/j4mie/idiorm/commit/089336fcd9417d6c4e40be802b3dec37b11c7d51
     */
    public function orderByExpr($clause)
    {
        $this->order_by[] = $clause;
        return $this;
    }

    /**
     * Add a column to the list of columns to GROUP BY
     *
     * @param string $column_name column name
     *
     * @return Idiorm current instance
     */
    public function groupBy($column_name)
    {
        $column_name = $this->quoteIdentifier($column_name);
        $this->groupBy[] = $column_name;
        return $this;
    }

    /**
     * Build a SELECT statement based on the clauses that have
     * been passed to this instance by chaining method calls.
     *
     * @return string
     */
    protected function buildSelect()
    {
        // If the query is raw, just set the $this->values to be
        // the raw query parameters and return the raw query
        if ($this->is_raw_query) {
            $this->values = $this->raw_parameters;
            return $this->rawQuery;
        }

        // Build and return the full SELECT statement by concatenating
        // the results of calling each separate builder method.
        return $this->joinIfNotEmpty(
            " ",
            array(
                $this->buildSelectStart(),
                $this->buildJoin(),
                $this->buildWhere(),
                $this->buildGroupBy(),
                $this->buildOrderBy(),
                $this->buildLimit(),
                $this->buildOffset(),
            )
        );
    }

    /**
     * Build the start of the SELECT statement
     *
     * @return string
     */
    protected function buildSelectStart()
    {
        $result_columns = join(', ', $this->result_columns);

        if ($this->distinct) {
            $result_columns = 'DISTINCT ' . $result_columns;
        }

        $fragment = "SELECT {$result_columns} FROM " . $this->quoteIdentifier($this->table_name);

        if (!is_null($this->tableAlias)) {
            $fragment .= " " . $this->quoteIdentifier($this->tableAlias);
        }
        return $fragment;
    }

    /**
     * Build the JOIN sources
     *
     * @return string
     */
    protected function buildJoin()
    {
        if (count($this->join_sources) === 0) {
            return '';
        }

        return join(" ", $this->join_sources);
    }

    /**
     * Build the WHERE clause(s)
     *
     * @return string
     */
    protected function buildWhere()
    {
        // If there are no WHERE clauses, return empty string
        if (count($this->where_conditions) === 0) {
            return '';
        }

        $where_conditions = array();
        foreach ($this->where_conditions as $condition) {
            $where_conditions[] = $condition[self::WHERE_FRAGMENT];
            $this->values = array_merge($this->values, $condition[self::WHERE_VALUES]);
        }

        return "WHERE " . join(" AND ", $where_conditions);
    }

    /**
     * Build GROUP BY
     *
     * @return string
     */
    protected function buildGroupBy()
    {
        if (count($this->groupBy) === 0) {
            return '';
        }
        return "GROUP BY " . join(", ", $this->groupBy);
    }

    /**
     * Build ORDER BY
     *
     * @return string
     */
    protected function buildOrderBy()
    {
        if (count($this->order_by) === 0) {
            return '';
        }
        return "ORDER BY " . join(", ", $this->order_by);
    }

    /**
     * Build LIMIT
     *
     * @return string
     */
    protected function buildLimit()
    {
        if (!is_null($this->limit)) {
            return "LIMIT " . $this->limit;
        }
        return '';
    }

    /**
     * Build OFFSET
     *
     * @return string
     */
    protected function buildOffset()
    {
        if (!is_null($this->offset)) {
            return "OFFSET " . $this->offset;
        }
        return '';
    }

    /**
     * Wrapper around PHP's join function which
     * only adds the pieces if they are not empty.
     *
     * @param string $glue   glue with which to join
     * @param array  $pieces array of string to be joined
     *
     * @return string string of values joined by glue
     */
    protected function joinIfNotEmpty($glue, $pieces)
    {
        $filtered_pieces = array();
        foreach ($pieces as $piece) {
            if (is_string($piece)) {
                $piece = trim($piece);
            }
            if (!empty($piece)) {
                $filtered_pieces[] = $piece;
            }
        }
        return join($glue, $filtered_pieces);
    }

    /**
     * Quote a string that is used as an identifier
     * (table names, column names etc). This method can
     * also deal with dot-separated identifiers eg table.column
     *
     * @param string $identifier identifier string
     *
     * @return string dot separated identifier string
     */
    protected function quoteIdentifier($identifier)
    {
        $parts = explode('.', $identifier);
        $parts = array_map(array($this, 'quoteIdentifierPart'), $parts);
        return join('.', $parts);
    }

    /**
     * This method performs the actual quoting of a single
     * part of an identifier, using the identifier quote
     * character specified in the config (or autodetected).
     *
     * @param string $part part of query to be quoted
     *
     * @return string part surrounded by quotes
     */
    protected function quoteIdentifierPart($part)
    {
        if ($part === '*') {
            return $part;
        }
        $quote_character = self::$config['identifier_quote_character'];
        return $quote_character . $part . $quote_character;
    }

    /**
     * Create a cache key for the given query and parameters.
     *
     * @param string $query      query to cache
     * @param array  $parameters array of parameters to bind to query
     *
     * @return string sha1 hash key
     */
    protected static function createCacheKey($query, $parameters)
    {
        $parameter_string = join(',', $parameters);
        $key = $query . ':' . $parameter_string;
        return sha1($key);
    }

    /**
     * Check the query cache for the given cache key. If a value
     * is cached for the key, return the value. Otherwise, return false.
     *
     * @param string $cache_key key from which to retrieve value
     *
     * @return mixed value if the key is set, false otherwise
     */
    protected static function checkQueryCache($cache_key)
    {
        if (isset(self::$query_cache[$cache_key])) {
            return self::$query_cache[$cache_key];
        }
        return false;
    }

    /**
     * Clear the query cache
     *
     * @return none
     */
    public static function clearCache()
    {
        self::$query_cache = array();
    }

    /**
     * Add the given value to the query cache.
     *
     * @param strign $cache_key name of key to set
     * @param string $value     value to add to cache
     *
     * @return none
     */
    protected static function cacheQueryResult($cache_key, $value)
    {
        self::$query_cache[$cache_key] = $value;
    }

    /**
     * Execute the SELECT query that has been built up by chaining methods
     * on this class. Return an array of rows as associative arrays.
     *
     * @return array rows as associate arrays
     */
    protected function run()
    {
        $query = $this->buildSelect();
        $caching_enabled = self::$config['caching'];

        if ($caching_enabled) {
            $cache_key = self::createCacheKey($query, $this->values);
            $cached_result = self::checkQueryCache($cache_key);

            if ($cached_result !== false) {
                return $cached_result;
            }
        }

        self::logQuery($query, $this->values);
        $statement = self::$database->prepare($query);
        $statement->execute($this->values);

        $rows = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        if ($caching_enabled) {
            self::cacheQueryResult($cache_key, $rows);
        }

        return $rows;
    }

    /**
     * Return the raw data wrapped by this ORM
     * instance as an associative array. Column
     * names may optionally be supplied as arguments,
     * if so, only those keys will be returned.
     *
     * @return array associate array of all data in this instance
     */
    public function asArray()
    {
        if (func_num_args() === 0) {
            return $this->data;
        }
        $args = func_get_args();
        return array_intersect_key($this->data, array_flip($args));
    }

    /**
     * Return the value of a property of this object (database row)
     * or null if not present.
     *
     * @param string $key column from which to get value
     *
     * @return mixed value assigned to that object or null if not present
     */
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Return the name of the column in the database table which contains
     * the primary key ID of the row.
     *
     * @return string name of column used as primary key
     */
    protected function getIdColumnName()
    {
        if (!is_null($this->instance_id_column)) {
            return $this->instance_id_column;
        }
        if (isset(self::$config['id_column_overrides'][$this->table_name])) {
            return self::$config['id_column_overrides'][$this->table_name];
        } else {
            return self::$config['id_column'];
        }
    }

    /**
     * Get the primary key ID of this object.
     *
     * @return mixed primary key value
     */
    public function id()
    {
        return $this->get($this->getIdColumnName());
    }

    /**
     * Set a property to a particular value on this object.
     * Flags that property as 'dirty' so it will be saved to the
     * database when save() is called.
     *
     * @param string $key   column to set
     * @param string $value value to assign
     *
     * @return none
     *
     * @link https://github.com/j4mie/idiorm/commit/2463ca7ace76a3b8bb9216910ca6f6d8e3f40e15
     */
    public function set($key, $value = null)
    {
        if (!is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $field => $value) {
            $this->data[$field] = $value;
            $this->dirty_fields[$field] = $value;
        }
    }

    /**
     * Check whether the given field has been changed since this
     * object was saved.
     *
     * @param string $key key to check
     *
     * @return boolean true if field has been changed since last save
     */
    public function isDirty($key)
    {
        return isset($this->dirty_fields[$key]);
    }

    /**
     * Save any fields which have been modified on this object
     * to the database.
     *
     * @return boolean true on success, false on failure
     */
    public function save()
    {
        $query = array();
        $values = array_values($this->dirty_fields);

        if (!$this->is_new) { // UPDATE
            // If there are no dirty values, do nothing
            if (count($values) == 0) {
                return true;
            }
            $query = $this->buildUpdate();
            $values[] = $this->id();
        } else { // INSERT
            $query = $this->buildInsert();
        }

        self::logQuery($query, $values);
        $statement = self::$database->prepare($query);
        $success = $statement->execute($values);

        // If we've just inserted a new record, set the ID of this object
        if ($this->is_new) {
            $this->is_new = false;
            if (is_null($this->id())) {
                $this->data[$this->getIdColumnName()] = self::$database->lastInsertId();
            }
        }

        $this->dirty_fields = array();
        return $success;
    }

    /**
     * Build an UPDATE query
     *
     * @return string UPDATE query string
     */
    protected function buildUpdate()
    {
        $query = array();
        $query[] = "UPDATE {$this->quoteIdentifier($this->table_name)} SET";

        $field_list = array();
        $keys = array_keys($this->dirty_fields);
        foreach ($keys as $key) {
            $field_list[] = "{$this->quoteIdentifier($key)} = ?";
        }
        $query[] = join(", ", $field_list);
        $query[] = "WHERE";
        $query[] = $this->quoteIdentifier($this->getIdColumnName());
        $query[] = "= ?";
        return join(" ", $query);
    }

    /**
     * Build an INSERT query
     *
     * @return string INSERT query string
     */
    protected function buildInsert()
    {
        $query[] = "INSERT INTO";
        $query[] = $this->quoteIdentifier($this->table_name);
        $field_list = array_map(array($this, 'quoteIdentifier'), array_keys($this->dirty_fields));
        $query[] = "(" . join(", ", $field_list) . ")";
        $query[] = "VALUES";

        $placeholders = $this->createPlaceholders(count($this->dirty_fields));
        $query[] = "({$placeholders})";
        return join(" ", $query);
    }

    /**
     * Delete this record from the database
     *
     * @return boolean true on success, false on failure
     */
    public function delete()
    {
        $query = join(
            " ",
            array(
                "DELETE FROM",
                $this->quoteIdentifier($this->table_name),
                "WHERE",
                $this->quoteIdentifier($this->getIdColumnName()),
                "= ?",
            )
        );
        $params = array($this->id());
        self::logQuery($query, $params);
        $statement = self::$database->prepare($query);
        return $statement->execute($params);
    }

    /**
     * delete many records from the database
     *
     * @link https://github.com/gabrielhora/idiorm/commit/185940c0334cebe5a276954f74b97dc1a27faf92
     *
     * @return boolean true on success, false on failure
     */
    public function deleteMany()
    {
        // Build and return the full DELETE statement by concatenating
        // the results of calling each separate builder method.
        
        $query = $this->joinIfNotEmpty(
            " ",
            array("DELETE FROM",
                $this->quoteIdentifier($this->table_name),
                $this->buildWhere()
            )
        );

        self::logQuery($query, $this->values);
        $statement = self::$database->prepare($query);
        return $statement->execute($this->values);
    }

    // --------------------- //
    // --- MAGIC METHODS --- //
    // --------------------- //
    
    /**
     * Magic getter
     * 
     * @param string $key key for which to get value
     *
     * @return mixed value assigned to key, or null if doesn't exist
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic setter
     *
     * @param string $key   key to set
     * @param value  $value value to assign to key
     *
     * @return none
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic isset
     *
     * @param string $key key to check if isset
     *
     * @return boolean true on isset, false otherwise
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }
}


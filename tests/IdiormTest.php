<?php


class IdiormTest extends PHPUnit_Framework_TestCase
{
    private $_db;

    public function setUp()
    {
        // Enable logging
        Orm::configure('logging', true);

        // Set up the dummy database connection
        $this->_db = new PDO('sqlite::memory:');
        $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $this->_db->exec("CREATE TABLE widget
(id INTEGER NOT NULL,
primary_key INTEGER, /*Used by the config test in a case where the primary key isn't id */
widget_id INTEGER, /*used by the config tests as an override of the primary key column name */
new_id INTEGER, /*used by the config tests as an override of the default primary key column */
name VARCHAR(50),
age INTEGER,
size VARCHAR(10),
PRIMARY KEY (id),
UNIQUE (id))");

$this->_db->exec("CREATE TABLE widget_handle
(id INTEGER NOT NULL,
widget_id INTEGER NOT NULL,
widget_handle_id INTEGER, /*used by the config tests as an override of the primary key column name */
new_id INTEGER, /*used by the config tests as an override of the default primary key column */
PRIMARY KEY (id),
UNIQUE (id))");

$this->_db->exec("CREATE TABLE widget_nozzle
(id INTEGER NOT NULL,
widget_id INTEGER NOT NULL,
primary_key INTEGER NOT NULL,
new_id INTEGER, /*used by the config tests as an override of the default primary key column */
PRIMARY KEY (id),
UNIQUE (id))");
        $STH = $this->_db->prepare("INSERT INTO widget (id, name, age) values (:id, :name, :age)");
        $STH->execute(array(
            'name' => 'Fred',
            'age' => 10,
            'id' => 1)
        );

        Orm::set_db($this->_db);
    }

    public function tearDown()
    {
        $this->_db->exec("DROP TABLE `widget`;");
        unset($this->_db);
    }

    public function testFindManyQuery()
    {
        ORM::for_table('widget')->find_many();
        $expected = "SELECT * FROM `widget`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFindOneQuery()
    {
        ORM::for_table('widget')->find_one();
        $expected = "SELECT * FROM `widget` LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereIdIsFindOne()
    {
        ORM::for_table('widget')->where_id_is(5)->find_one();
        $expected = "SELECT * FROM `widget` WHERE `id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFindOneById()
    {
        ORM::for_table('widget')->find_one(5);
        $expected = "SELECT * FROM `widget` WHERE `id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testCount()
    {
        ORM::for_table('widget')->count();
        $expected = "SELECT COUNT(*) AS `count` FROM `widget` LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereNameEqualsFindOne()
    {
    ORM::for_table('widget')->where('name', 'Fred')->find_one();
    $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' LIMIT 1";
    $this->assertEquals($expected, ORM::get_last_query());        
    }

    public function testWhereCol1EqualsAndCol2Equals()
    {
    ORM::for_table('widget')->where('name', 'Fred')->where('age', 10)->find_one();
    $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' AND `age` = '10' LIMIT 1";
    $this->assertEquals($expected, ORM::get_last_query());        
    }

    public function testWhereCol1NotEqualsFindMany()
    {   
        ORM::for_table('widget')->where_not_equal('name', 'Fred')->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` != 'Fred'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereCol1LikeFindOne()
    {   
        ORM::for_table('widget')->where_like('name', '%Fred%')->find_one();
        $expected = "SELECT * FROM `widget` WHERE `name` LIKE '%Fred%' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereCol1NotLikeFindOne()
    {
        ORM::for_table('widget')->where_not_like('name', '%Fred%')->find_one();
        $expected = "SELECT * FROM `widget` WHERE `name` NOT LIKE '%Fred%' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereCol1InFindMany()
    {
        ORM::for_table('widget')->where_in('name', array('Fred', 'Joe'))->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` IN ('Fred', 'Joe')";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereColNotInFindMany()
    {
        ORM::for_table('widget')->where_not_in('name', array('Fred', 'Joe'))->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` NOT IN ('Fred', 'Joe')";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereLimitEqualsFindMany()
    {
        ORM::for_table('widget')->limit(5)->find_many();
        $expected = "SELECT * FROM `widget` LIMIT 5";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereLimitEqualsAndOffsetEqualsFindMany()
    {
        ORM::for_table('widget')->limit(5)->offset(5)->find_many();
        $expected = "SELECT * FROM `widget` LIMIT 5 OFFSET 5";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereOrderByDescFindOne()
    {
        ORM::for_table('widget')->order_by_desc('name')->find_one();
        $expected = "SELECT * FROM `widget` ORDER BY `name` DESC LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereOrderByAscFindOne()
    {
        ORM::for_table('widget')->order_by_asc('name')->find_one();
        $expected = "SELECT * FROM `widget` ORDER BY `name` ASC LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testCol1OrderByAscAndCol2OrderByDescFindOne()
    {
        ORM::for_table('widget')->order_by_asc('name')->order_by_desc('age')->find_one();
        $expected = "SELECT * FROM `widget` ORDER BY `name` ASC, `age` DESC LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testGroupByFindMany()
    {
        ORM::for_table('widget')->group_by('name')->find_many();
        $expected = "SELECT * FROM `widget` GROUP BY `name`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testCol1GroupByAndCol2GroupByFindMany()
    {
        ORM::for_table('widget')->group_by('name')->group_by('age')->find_many();
        $expected = "SELECT * FROM `widget` GROUP BY `name`, `age`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereCol1EqualsLimitEqualsOffsetOrderByAscFindMany()
    {
        ORM::for_table('widget')->where('name', 'Fred')->limit(5)->offset(5)->order_by_asc('name')->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' ORDER BY `name` ASC LIMIT 5 OFFSET 5";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereCol1LTAndCol1GTFindMany()
    {
        ORM::for_table('widget')->where_lt('age', 10)->where_gt('age', 5)->find_many();
        $expected = "SELECT * FROM `widget` WHERE `age` < '10' AND `age` > '5'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereCol1LTEandCol1GTEFindMany()
    {
        ORM::for_table('widget')->where_lte('age', 10)->where_gte('age', 5)->find_many();
        $expected = "SELECT * FROM `widget` WHERE `age` <= '10' AND `age` >= '5'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereColNullFindMany()
    {
        ORM::for_table('widget')->where_null('name')->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` IS NULL";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereColNotNullFindMany()
    {
        ORM::for_table('widget')->where_not_null('name')->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` IS NOT NULL";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereRawComplexFindMany()
    {
        ORM::for_table('widget')->where_raw('`name` = ? AND (`age` = ? OR `age` = ?)', array('Fred', 5, 10))->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' AND (`age` = '5' OR `age` = '10')";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereRawSimpleFindMany()
    {
        ORM::for_table('widget')->where_raw('`name` = "Fred"')->find_many();
        $expected = "SELECT * FROM `widget` WHERE `name` = \"Fred\"";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testWhereColEqualsAndWhereRawComplexAndWhereCol2EqualsFindMany()
    {
        ORM::for_table('widget')->where('age', 18)->where_raw('(`name` = ? OR `name` = ?)', array('Fred', 'Bob'))->where('size', 'large')->find_many();
        $expected = "SELECT * FROM `widget` WHERE `age` = '18' AND (`name` = 'Fred' OR `name` = 'Bob') AND `size` = 'large'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testRawQueryFindMany()
    {
        ORM::for_table('widget')->raw_query('SELECT `w`.* FROM `widget` w WHERE `name` = ? AND `age` = ?', array('Fred', 5))->find_many();
        $expected = "SELECT `w`.* FROM `widget` w WHERE `name` = 'Fred' AND `age` = '5'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testSelectColFindMany()
    {
        ORM::for_table('widget')->select('name')->find_many();
        $expected = "SELECT `name` FROM `widget`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testSelectCol1SelectCol2FindMany()
    {
        ORM::for_table('widget')->select('name')->select('age')->find_many();
        $expected = "SELECT `name`, `age` FROM `widget`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testSelectDotNotationFindMany()
    {
        ORM::for_table('widget')->select('widget.name')->find_many();
        $expected = "SELECT `widget`.`name` FROM `widget`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testSelectDotNotationAsFindMany()
    {
        ORM::for_table('widget')->select('widget.name', 'widget_name')->find_many();
        $expected = "SELECT `widget`.`name` AS `widget_name` FROM `widget`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testSelectExpressionCountFindMany()
    {
        ORM::for_table('widget')->select_expr('COUNT(*)', 'count')->find_many();
        $expected = "SELECT COUNT(*) AS `count` FROM `widget`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testJoinFindMany()
    {
        ORM::for_table('widget')->join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->find_many();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testInnerJoinFindMany()
    {
        ORM::for_table('widget')->inner_join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->find_many();
        $expected = "SELECT * FROM `widget` INNER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testLeftOuterJoinFindMany()
    {
        ORM::for_table('widget')->left_outer_join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->find_many();
        $expected = "SELECT * FROM `widget` LEFT OUTER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testRightOuterJoinFindMany()
    {
        ORM::for_table('widget')->right_outer_join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->find_many();
        $expected = "SELECT * FROM `widget` RIGHT OUTER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFullOuterJoinFindMany()
    {
        ORM::for_table('widget')->full_outer_join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->find_many();
        $expected = "SELECT * FROM `widget` FULL OUTER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testComplexJoinFindMany()
    {
        ORM::for_table('widget')
            ->join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))
            ->join('widget_nozzle', array('widget_nozzle.widget_id', '=', 'widget.id'))
            ->find_many();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id` JOIN `widget_nozzle` ON `widget_nozzle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testTableAliasFindMany()
    {
        ORM::for_table('widget')->table_alias('w')->find_many();
        $expected = "SELECT * FROM `widget` `w`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testJoinQuotedFindMany()
    {
        ORM::for_table('widget')->join('widget_handle', array('wh.widget_id', '=', 'widget.id'), 'wh')->find_many();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` `wh` ON `wh`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testJoinUnquotedFindMany()
    {
        ORM::for_table('widget')->join('widget_handle', "widget_handle.widget_id = widget.id")->find_many();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` ON widget_handle.widget_id = widget.id";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testDistinctSelectFindMany()
    {
        ORM::for_table('widget')->distinct()->select('name')->find_many();
        $expected = "SELECT DISTINCT `name` FROM `widget`";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testCreateORMObjectAndSave()
    {
        $widget = ORM::for_table('widget')->create();
        $widget->name = "Fred";
        $widget->age = 10;
        $widget->save();
        $expected = "INSERT INTO `widget` (`name`, `age`) VALUES ('Fred', '10')";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFindExistingObjectByIdUpdateAndSave()
    {
        $widget = ORM::for_table('widget')->find_one(1);
        $widget->name = "Fred";
        $widget->age = 10;
        $widget->save();
        $expected = "UPDATE `widget` SET `name` = 'Fred', `age` = '10' WHERE `id` = '1'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testCreateORMObjectFromExistingAndDelete()
    {
        $widget = ORM::for_table('widget')->find_one(1);
        $widget->delete();
        $expected = "DELETE FROM `widget` WHERE `id` = '1'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    // Regression tests
    public function testSelectAllFindOne()
    {
        $widget = ORM::for_table('widget')->select('widget.*')->find_one();
        $expected = "SELECT `widget`.* FROM `widget` LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    // Tests that alter Idiorm's config are done last
    public function testSetPrimaryKeyColumn()
    {
        ORM::configure('id_column', 'primary_key');
        ORM::for_table('widget')->find_one(5);
        $expected = "SELECT * FROM `widget` WHERE `primary_key` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testIDColumnOverrides()
    {
        //used to override the primary id column name.
        ORM::configure('id_column_overrides', array(
            'widget' => 'widget_id',
            'widget_handle' => 'widget_handle_id',
        ));

        ORM::for_table('widget')->find_one(5);
        $expected = "SELECT * FROM `widget` WHERE `widget_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());

        ORM::for_table('widget_handle')->find_one(5);
        $expected = "SELECT * FROM `widget_handle` WHERE `widget_handle_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFindOneByPrimaryKeyId()
    {
        ORM::for_table('widget_nozzle')->find_one(5);
        $expected = "SELECT * FROM `widget_nozzle` WHERE `primary_key` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFindOneWidgetByIdAndUseIdColumn()
    {
        //override the id column for this instance.
        ORM::for_table('widget')->use_id_column('new_id')->find_one(5);
        $expected = "SELECT * FROM `widget` WHERE `new_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFindOneWidgetHandleByIdAndUseIdColumn()
    {
        ORM::for_table('widget_handle')->use_id_column('new_id')->find_one(5);
        $expected = "SELECT * FROM `widget_handle` WHERE `new_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testFindOneWidgetNozzleByIdAndUseIdColumn()
    {
        ORM::for_table('widget_nozzle')->use_id_column('new_id')->find_one(5);
        $expected = "SELECT * FROM `widget_nozzle` WHERE `new_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }
    
    // Test caching. This is a bit of a hack.
    public function testCaching()
    {
        ORM::configure('caching', true);
        ORM::for_table('widget')->where('name', 'Fred')->where('age', 17)->find_one();
        ORM::for_table('widget')->where('name', 'Bob')->where('age', 42)->find_one();
        $expected = ORM::get_last_query();
        ORM::for_table('widget')->where('name', 'Fred')->where('age', 17)->find_one(); // this shouldn't run a query!
        $this->assertEquals($expected, ORM::get_last_query());
    }
}

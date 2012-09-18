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

        Orm::setDatabase($this->_db);
    }

    public function tearDown()
    {
        $this->_db->exec("DROP TABLE `widget`;");
        unset($this->_db);
    }

    public function testFindManyQuery()
    {
        ORM::forTable('widget')->findMany();
        $expected = "SELECT * FROM `widget`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFindOneQuery()
    {
        ORM::forTable('widget')->findOne();
        $expected = "SELECT * FROM `widget` LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereIdIsFindOne()
    {
        ORM::forTable('widget')->whereIdIs(5)->findOne();
        $expected = "SELECT * FROM `widget` WHERE `id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFindOneById()
    {
        ORM::forTable('widget')->findOne(5);
        $expected = "SELECT * FROM `widget` WHERE `id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testCount()
    {
        ORM::forTable('widget')->count();
        $expected = "SELECT COUNT(*) AS `count` FROM `widget` LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereNameEqualsFindOne()
    {
    ORM::forTable('widget')->where('name', 'Fred')->findOne();
    $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' LIMIT 1";
    $this->assertEquals($expected, ORM::getLastQuery());        
    }

    public function testWhereCol1EqualsAndCol2Equals()
    {
    ORM::forTable('widget')->where('name', 'Fred')->where('age', 10)->findOne();
    $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' AND `age` = '10' LIMIT 1";
    $this->assertEquals($expected, ORM::getLastQuery());        
    }

    public function testWhereCol1NotEqualsFindMany()
    {   
        ORM::forTable('widget')->whereNotEqual('name', 'Fred')->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` != 'Fred'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereCol1LikeFindOne()
    {   
        ORM::forTable('widget')->whereLike('name', '%Fred%')->findOne();
        $expected = "SELECT * FROM `widget` WHERE `name` LIKE '%Fred%' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereCol1NotLikeFindOne()
    {
        ORM::forTable('widget')->whereNotLike('name', '%Fred%')->findOne();
        $expected = "SELECT * FROM `widget` WHERE `name` NOT LIKE '%Fred%' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereCol1InFindMany()
    {
        ORM::forTable('widget')->whereIn('name', array('Fred', 'Joe'))->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` IN ('Fred', 'Joe')";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereColNotInFindMany()
    {
        ORM::forTable('widget')->whereNotIn('name', array('Fred', 'Joe'))->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` NOT IN ('Fred', 'Joe')";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereLimitEqualsFindMany()
    {
        ORM::forTable('widget')->limit(5)->findMany();
        $expected = "SELECT * FROM `widget` LIMIT 5";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereLimitEqualsAndOffsetEqualsFindMany()
    {
        ORM::forTable('widget')->limit(5)->offset(5)->findMany();
        $expected = "SELECT * FROM `widget` LIMIT 5 OFFSET 5";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereOrderByDescFindOne()
    {
        ORM::forTable('widget')->orderByDesc('name')->findOne();
        $expected = "SELECT * FROM `widget` ORDER BY `name` DESC LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereOrderByAscFindOne()
    {
        ORM::forTable('widget')->orderByAsc('name')->findOne();
        $expected = "SELECT * FROM `widget` ORDER BY `name` ASC LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testCol1OrderByAscAndCol2OrderByDescFindOne()
    {
        ORM::forTable('widget')->orderByAsc('name')->orderByDesc('age')->findOne();
        $expected = "SELECT * FROM `widget` ORDER BY `name` ASC, `age` DESC LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testOrderByRawClause()
    {
        ORM::forTable('widget')->orderByExpr('SOUNDEX(`name`)')->findOne();
        $expected = "SELECT * FROM `widget` ORDER BY SOUNDEX(`name`) LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testGroupByFindMany()
    {
        ORM::forTable('widget')->groupBy('name')->findMany();
        $expected = "SELECT * FROM `widget` GROUP BY `name`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testCol1GroupByAndCol2GroupByFindMany()
    {
        ORM::forTable('widget')->groupBy('name')->groupBy('age')->findMany();
        $expected = "SELECT * FROM `widget` GROUP BY `name`, `age`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereCol1EqualsLimitEqualsOffsetOrderByAscFindMany()
    {
        ORM::forTable('widget')->where('name', 'Fred')->limit(5)->offset(5)->orderByAsc('name')->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' ORDER BY `name` ASC LIMIT 5 OFFSET 5";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereCol1LTAndCol1GTFindMany()
    {
        ORM::forTable('widget')->whereLt('age', 10)->whereGt('age', 5)->findMany();
        $expected = "SELECT * FROM `widget` WHERE `age` < '10' AND `age` > '5'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereCol1LTEandCol1GTEFindMany()
    {
        ORM::forTable('widget')->whereLte('age', 10)->whereGte('age', 5)->findMany();
        $expected = "SELECT * FROM `widget` WHERE `age` <= '10' AND `age` >= '5'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereColNullFindMany()
    {
        ORM::forTable('widget')->whereNull('name')->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` IS NULL";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereColNotNullFindMany()
    {
        ORM::forTable('widget')->whereNotNull('name')->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` IS NOT NULL";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereRawComplexFindMany()
    {
        ORM::forTable('widget')->whereRaw('`name` = ? AND (`age` = ? OR `age` = ?)', array('Fred', 5, 10))->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` = 'Fred' AND (`age` = '5' OR `age` = '10')";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereRawSimpleFindMany()
    {
        ORM::forTable('widget')->whereRaw('`name` = "Fred"')->findMany();
        $expected = "SELECT * FROM `widget` WHERE `name` = \"Fred\"";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testWhereColEqualsAndWhereRawComplexAndWhereCol2EqualsFindMany()
    {
        ORM::forTable('widget')->where('age', 18)->whereRaw('(`name` = ? OR `name` = ?)', array('Fred', 'Bob'))->where('size', 'large')->findMany();
        $expected = "SELECT * FROM `widget` WHERE `age` = '18' AND (`name` = 'Fred' OR `name` = 'Bob') AND `size` = 'large'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testRawQueryWithParameters()
    {
        ORM::forTable('widget')->rawQuery('SELECT `w`.* FROM `widget` w WHERE `name` = ? AND `age` = ?', array('Fred', 5))->findMany();
        $expected = "SELECT `w`.* FROM `widget` w WHERE `name` = 'Fred' AND `age` = '5'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testRawQueryWithoutParameters()
    {
        ORM::forTable('widget')->rawQuery('SELECT `w`.* FROM `widget` w')->findMany();
        $expected = "SELECT `w`.* FROM `widget` w";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testSelectColFindMany()
    {
        ORM::forTable('widget')->select('name')->findMany();
        $expected = "SELECT `name` FROM `widget`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testSelectCol1SelectCol2FindMany()
    {
        ORM::forTable('widget')->select('name')->select('age')->findMany();
        $expected = "SELECT `name`, `age` FROM `widget`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testSelectDotNotationFindMany()
    {
        ORM::forTable('widget')->select('widget.name')->findMany();
        $expected = "SELECT `widget`.`name` FROM `widget`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testSelectDotNotationAsFindMany()
    {
        ORM::forTable('widget')->select('widget.name', 'widget_name')->findMany();
        $expected = "SELECT `widget`.`name` AS `widget_name` FROM `widget`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testSelectExpressionCountFindMany()
    {
        ORM::forTable('widget')->selectExpression('COUNT(*)', 'count')->findMany();
        $expected = "SELECT COUNT(*) AS `count` FROM `widget`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testJoinFindMany()
    {
        ORM::forTable('widget')->join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->findMany();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testInnerJoinFindMany()
    {
        ORM::forTable('widget')->innerJoin('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->findMany();
        $expected = "SELECT * FROM `widget` INNER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testLeftOuterJoinFindMany()
    {
        ORM::forTable('widget')->leftOuterJoin('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->findMany();
        $expected = "SELECT * FROM `widget` LEFT OUTER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testRightOuterJoinFindMany()
    {
        ORM::forTable('widget')->rightOuterJoin('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->findMany();
        $expected = "SELECT * FROM `widget` RIGHT OUTER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFullOuterJoinFindMany()
    {
        ORM::forTable('widget')->fullOuterJoin('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))->findMany();
        $expected = "SELECT * FROM `widget` FULL OUTER JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testComplexJoinFindMany()
    {
        ORM::forTable('widget')
            ->join('widget_handle', array('widget_handle.widget_id', '=', 'widget.id'))
            ->join('widget_nozzle', array('widget_nozzle.widget_id', '=', 'widget.id'))
            ->findMany();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` ON `widget_handle`.`widget_id` = `widget`.`id` JOIN `widget_nozzle` ON `widget_nozzle`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testTableAliasFindMany()
    {
        ORM::forTable('widget')->tableAlias('w')->findMany();
        $expected = "SELECT * FROM `widget` `w`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testJoinQuotedFindMany()
    {
        ORM::forTable('widget')->join('widget_handle', array('wh.widget_id', '=', 'widget.id'), 'wh')->findMany();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` `wh` ON `wh`.`widget_id` = `widget`.`id`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testJoinUnquotedFindMany()
    {
        ORM::forTable('widget')->join('widget_handle', "widget_handle.widget_id = widget.id")->findMany();
        $expected = "SELECT * FROM `widget` JOIN `widget_handle` ON widget_handle.widget_id = widget.id";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testDistinctSelectFindMany()
    {
        ORM::forTable('widget')->distinct()->select('name')->findMany();
        $expected = "SELECT DISTINCT `name` FROM `widget`";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testCreateORMObjectAndSave()
    {
        $widget = ORM::forTable('widget')->create();
        $widget->name = "Fred";
        $widget->age = 10;
        $widget->save();
        $expected = "INSERT INTO `widget` (`name`, `age`) VALUES ('Fred', '10')";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFindExistingObjectByIdUpdateAndSave()
    {
        $widget = ORM::forTable('widget')->findOne(1);
        $widget->name = "Fred";
        $widget->age = 10;
        $widget->save();
        $expected = "UPDATE `widget` SET `name` = 'Fred', `age` = '10' WHERE `id` = '1'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testUpdateMultipleBySet()
    {
        $widget = ORM::forTable('widget')->findOne(1);
        $widget->set(array("name" => "Fred", "age" => 10));
        $widget->save();
        $expected = "UPDATE `widget` SET `name` = 'Fred', `age` = '10' WHERE `id` = '1'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testCreateORMObjectFromExistingAndDelete()
    {
        $widget = ORM::forTable('widget')->findOne(1);
        $widget->delete();
        $expected = "DELETE FROM `widget` WHERE `id` = '1'";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    // Regression tests
    public function testSelectAllFindOne()
    {
        $widget = ORM::forTable('widget')->select('widget.*')->findOne();
        $expected = "SELECT `widget`.* FROM `widget` LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    // Tests that alter Idiorm's config are done last
    public function testSetPrimaryKeyColumn()
    {
        ORM::configure('id_column', 'primary_key');
        ORM::forTable('widget')->findOne(5);
        $expected = "SELECT * FROM `widget` WHERE `primary_key` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testIDColumnOverrides()
    {
        //used to override the primary id column name.
        ORM::configure('id_column_overrides', array(
            'widget' => 'widget_id',
            'widget_handle' => 'widget_handle_id',
        ));

        ORM::forTable('widget')->findOne(5);
        $expected = "SELECT * FROM `widget` WHERE `widget_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());

        ORM::forTable('widget_handle')->findOne(5);
        $expected = "SELECT * FROM `widget_handle` WHERE `widget_handle_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFindOneByPrimaryKeyId()
    {
        ORM::forTable('widget_nozzle')->findOne(5);
        $expected = "SELECT * FROM `widget_nozzle` WHERE `primary_key` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFindOneWidgetByIdAndUseIdColumn()
    {
        //override the id column for this instance.
        ORM::forTable('widget')->useIdColumn('new_id')->findOne(5);
        $expected = "SELECT * FROM `widget` WHERE `new_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFindOneWidgetHandleByIdAndUseIdColumn()
    {
        ORM::forTable('widget_handle')->useIdColumn('new_id')->findOne(5);
        $expected = "SELECT * FROM `widget_handle` WHERE `new_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }

    public function testFindOneWidgetNozzleByIdAndUseIdColumn()
    {
        ORM::forTable('widget_nozzle')->useIdColumn('new_id')->findOne(5);
        $expected = "SELECT * FROM `widget_nozzle` WHERE `new_id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::getLastQuery());
    }
    
    // Test caching. This is a bit of a hack.
    public function testCaching()
    {
        ORM::configure('caching', true);
        ORM::forTable('widget')->where('name', 'Fred')->where('age', 17)->findOne();
        ORM::forTable('widget')->where('name', 'Bob')->where('age', 42)->findOne();
        $expected = ORM::getLastQuery();
        ORM::forTable('widget')->where('name', 'Fred')->where('age', 17)->findOne(); // this shouldn't run a query!
        $this->assertEquals($expected, ORM::getLastQuery());
    }
}

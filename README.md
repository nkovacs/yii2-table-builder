Yii 2 migration table builder
==============================
Table builder migration helper extension for Yii 2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist nkovacs/yii2-table-builder "*"
```

or add

```
"nkovacs/yii2-table-builder": "*"
```

to the require section of your `composer.json` file.


Usage
-----

In order to use this extension, your migrations must extend from `\nkovacs\tablebuilder\Migration`.
Once the extension is installed, it will override the migrate command's templateFile property
during application bootstrap process, allowing you to use the extension's Migration class
in newly created migrations, provided you did not override templateFile yourself.

An example migration:

    <?php

    use yii\db\Schema;

    class m140701_113939_test extends \nkovacs\tablebuilder\Migration
    {
        public $tables;

        public function init()
        {
            parent::init();
            $this->tables = [
                '{{%table_a}}' => [
                    'id'   => Schema::TYPE_PK,
                    'name' => Schema::TYPE_STRING,
                ],
                '{{%table_b}}' => [
                    'id'   => Schema::TYPE_PK,
                    'name' => Schema::TYPE_STRING,
                ],
                '{{%table_c}}' => [
                    'id'      => Schema::TYPE_PK,
                    'user_id' => [Schema::TYPE_INTEGER, '{{%user}}', 'id', 'delete' => 'SET NULL', 'update' => 'CASCADE'],
                    'a_id'    => [Schema::TYPE_INTEGER, '{{%table_a}}', 'id', 'delete' => 'RESTRICT', 'update' => 'CASCADE'],
                    'b_id'    => [Schema::TYPE_INTEGER, '{{%table_b}}', 'id', 'delete' => 'RESTRICT', 'update' => 'CASCADE'],
                    'name'    => Schema::TYPE_STRING,
                ],
                '{{%table_d}}' => [
                    'a_id'    => [Schema::TYPE_INTEGER, '{{%table_a}}', 'id', 'delete' => 'RESTRICT', 'update' => 'CASCADE'],
                    'b_id'    => [Schema::TYPE_INTEGER, '{{%table_b}}', 'id', 'delete' => 'RESTRICT', 'update' => 'CASCADE'],
                    ['primary' => ['a_id', 'b_id']],
                ]
            ];
        }

        public function up()
        {
            $this->build($this->tables);
        }

        public function down()
        {
            $this->tearDown($this->tables);
        }
    }

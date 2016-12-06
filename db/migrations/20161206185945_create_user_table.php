<?php

use Phinx\Migration\AbstractMigration;

class CreateUserTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('user');
        $table->addColumn('uuid', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('employee_id', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('first_name', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('last_name', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('username', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('password', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('active', 'enum', ['values' => ['Yes', 'No'], 'default' => 'Yes', 'null' => false])
              ->addColumn('locked', 'enum', ['values' => ['No','Yes'], 'default' => 'No', 'null' => false])
              ->addColumn('login_attempts', 'integer')
              ->addColumn('block_until', 'datetime', ['null' => true])
              ->addColumn('last_updated', 'datetime')
              ->addIndex('uuid', ['unique' => true])
              ->addIndex('employee_id', ['unique' => true])
              ->addIndex('username', ['unique' => true])
              ->addIndex('email', ['unique' => true])
              ->create();
    }
}

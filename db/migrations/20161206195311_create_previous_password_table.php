<?php

use Phinx\Migration\AbstractMigration;

class CreatePreviousPasswordTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('previous_password');
        $table->addColumn('user_id', 'integer', ['null' => false])
              ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('created', 'datetime')
              ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->create();
    }
}

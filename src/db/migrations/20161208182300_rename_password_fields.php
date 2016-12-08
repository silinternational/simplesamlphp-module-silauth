<?php

use Phinx\Migration\AbstractMigration;

class RenamePasswordFields extends AbstractMigration
{
    public function change()
    {
        $userTable = $this->table('user');
        $userTable->renameColumn('password', 'password_hash')
                  ->update();
        
        $previousPasswordTable = $this->table('previous_password');
        $previousPasswordTable->renameColumn('password', 'password_hash')
                              ->update();
    }
}

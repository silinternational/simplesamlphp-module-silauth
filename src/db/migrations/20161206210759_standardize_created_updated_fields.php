<?php

use Phinx\Migration\AbstractMigration;

class StandardizeCreatedUpdatedFields extends AbstractMigration
{
    public function change()
    {
        $userTable = $this->table('user');
        $userTable->removeColumn('last_updated')
                  ->addTimestamps()
                  ->update();
        
        $previousPasswordTable = $this->table('previous_password');
        $previousPasswordTable->removeColumn('created')
                  ->addTimestamps()
                  ->update();
    }
}

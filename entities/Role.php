<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");

class Role extends AbstractEntity implements IEntity {
    private int $pk_role;
    private ?string $role = null;

    public function getPkRole(): int {
        return $this->pk_role;
    }
    public function setPkRole(int $pk_role): void {
        $this->pk_role = $pk_role;
    }

    public function getRole(): ?string {
        return $this->role;
    }
    public function setRole(?string $role): void {
        $this->role = $role;
    }

    // Role::createFromRow($row) (GET)
    public static function createFromRow($row): Role {
        $role = new Role();
        $role->setPkRole( intval($row->pk_role) );
        $role->setRole( $row->role );
        return $role;
    }
}
?>
<?php

use PHPUnit\Framework\TestCase;

class NavTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('StaffNav')) {
            require_once INCLUDE_DIR . 'class.nav.php';
        }
    }

    private function makeStaffUser(bool $isAdmin = false, bool $canManageKb = false): object
    {
        return new class($isAdmin, $canManageKb) {
            private bool $admin;
            private bool $kb;
            public function __construct(bool $admin, bool $kb) {
                $this->admin = $admin;
                $this->kb = $kb;
            }
            public function isAdmin(): bool { return $this->admin; }
            public function canManageKb(): bool { return $this->kb; }
        };
    }

    public function testStaffNavHasTicketsTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('tickets', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testStaffNavHasTasksTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('tasks', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testStaffNavHasInventoryTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('inventory', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testStaffNavHasDirectoryTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('directory', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testStaffNavHasProfileTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('profile', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testStaffNavNoKbTabWithoutPermission(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        $this->assertArrayNotHasKey('kbase', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testStaffNavKbTabWithPermission(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, true);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('kbase', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testAdminNavHasDashboardTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(true);
        $nav = new StaffNav('admin');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('dashboard', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testAdminNavHasSettingsTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(true);
        $nav = new StaffNav('admin');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('settings', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testAdminNavHasEmailsTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(true);
        $nav = new StaffNav('admin');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('emails', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testAdminNavHasTopicsTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(true);
        $nav = new StaffNav('admin');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('topics', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testAdminNavHasStaffTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(true);
        $nav = new StaffNav('admin');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('staff', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testAdminNavHasDeptsTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(true);
        $nav = new StaffNav('admin');
        $tabs = $nav->getTabs();
        $this->assertArrayHasKey('depts', $tabs);
        unset($GLOBALS['thisuser']);
    }

    public function testSetTabActive(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $result = $nav->setTabActive('tickets');
        $this->assertTrue($result);
        $this->assertEquals('tickets', $nav->getActiveTab());
        unset($GLOBALS['thisuser']);
    }

    public function testSetTabActiveInvalidTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $result = $nav->setTabActive('nonexistent');
        $this->assertFalse($result);
        unset($GLOBALS['thisuser']);
    }

    public function testSetTabActiveSwitches(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $nav->setTabActive('tickets');
        $nav->setTabActive('tasks');
        $this->assertEquals('tasks', $nav->getActiveTab());
        $tabs = $nav->getTabs();
        $this->assertTrue($tabs['tasks']['active']);
        $this->assertFalse($tabs['tickets']['active']);
        unset($GLOBALS['thisuser']);
    }

    public function testAddSubMenu(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $nav->setTabActive('tickets');
        $nav->addSubMenu(['desc' => 'Open', 'href' => 'tickets.php?status=open']);
        $submenu = $nav->getSubMenu();
        $this->assertCount(1, $submenu);
        $this->assertEquals('Open', $submenu[0]['desc']);
        unset($GLOBALS['thisuser']);
    }

    public function testAddSubMenuToSpecificTab(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $nav->addSubMenu(['desc' => 'Item1'], 'tasks');
        $submenu = $nav->getSubMenu('tasks');
        $this->assertCount(1, $submenu);
        unset($GLOBALS['thisuser']);
    }

    public function testGetSubMenuEmpty(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $nav->setTabActive('tickets');
        $submenu = $nav->getSubMenu();
        $this->assertNull($submenu);
        unset($GLOBALS['thisuser']);
    }

    public function testGetActiveTabNull(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $this->assertNull($nav->getActiveTab());
        unset($GLOBALS['thisuser']);
    }

    public function testTabHasDescAndHref(): void
    {
        $GLOBALS['thisuser'] = $this->makeStaffUser(false, false);
        $nav = new StaffNav('staff');
        $tabs = $nav->getTabs();
        foreach ($tabs as $tab) {
            $this->assertArrayHasKey('desc', $tab);
            $this->assertArrayHasKey('href', $tab);
            $this->assertArrayHasKey('title', $tab);
        }
        unset($GLOBALS['thisuser']);
    }
}

<?php

use PHPUnit\Framework\TestCase;

class StaffTest extends TestCase
{
    private array $staffRow;

    protected function setUp(): void
    {
        if (!class_exists('Dept')) {
            eval('class Dept {
                public $id;
                public $managerId = 0;
                public function __construct($id = 0) { $this->id = $id; }
                public function getManagerId() { return $this->managerId; }
                public function getId() { return $this->id; }
            }');
        }
        if (!class_exists('Staff')) {
            require_once INCLUDE_DIR . 'class.staff.php';
        }

        $this->staffRow = [
            'staff_id' => 1,
            'group_id' => 1,
            'dept_id' => 1,
            'firstname' => 'john',
            'lastname' => 'doe',
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
            'passwd' => password_hash('secret123', PASSWORD_DEFAULT),
            'signature' => 'Best regards',
            'isactive' => 1,
            'isadmin' => 0,
            'isvisible' => 1,
            'onvacation' => 0,
            'timezone_offset' => '+03:00',
            'daylight_saving' => 1,
            'auto_refresh_rate' => 30,
            'max_page_size' => 25,
            'change_passwd' => 0,
            'dept_access' => '1,2,3',
            'group_enabled' => 1,
            'can_create_tickets' => 1,
            'can_edit_tickets' => 1,
            'can_delete_tickets' => 0,
            'can_close_tickets' => 1,
            'can_transfer_tickets' => 0,
            'can_ban_emails' => 0,
            'can_manage_kb' => 0,
        ];

        DatabaseMock::reset();
    }

    private function setupStaffLookup(array $overrides = []): void
    {
        $row = array_merge($this->staffRow, $overrides);
        DatabaseMock::setQueryResult('SELECT * FROM ' . STAFF_TABLE, [$row]);
    }

    public function testLookupById(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals(1, $staff->getId());
    }

    public function testLookupByUsername(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff('jdoe');
        $this->assertEquals(1, $staff->getId());
    }

    public function testLookupNotFound(): void
    {
        $staff = new Staff(999);
        $this->assertEquals(0, $staff->getId());
    }

    public function testGetName(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals('John doe', $staff->getName());
    }

    public function testGetFirstName(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals('John', $staff->getFirstName());
    }

    public function testGetLastName(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals('Doe', $staff->getLastName());
    }

    public function testGetEmail(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals('jdoe@example.com', $staff->getEmail());
    }

    public function testGetUserName(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals('jdoe', $staff->getUserName());
    }

    public function testGetDeptId(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals(1, $staff->getDeptId());
    }

    public function testGetGroupId(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals(1, $staff->getGroupId());
    }

    public function testGetSignature(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals('Best regards', $staff->getSignature());
    }

    public function testAppendMySignature(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->appendMySignature());
    }

    public function testAppendMySignatureEmpty(): void
    {
        $this->setupStaffLookup(['signature' => '']);
        $staff = new Staff(1);
        $this->assertFalse($staff->appendMySignature());
    }

    public function testCheckPasswdBcrypt(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->check_passwd('secret123'));
    }

    public function testCheckPasswdBcryptWrong(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertFalse($staff->check_passwd('wrongpassword'));
    }

    public function testCheckPasswdMd5Legacy(): void
    {
        $md5hash = md5('legacy123');
        $this->setupStaffLookup(['passwd' => $md5hash]);
        DatabaseMock::setAffectedRows(1);
        $staff = new Staff(1);
        $this->assertTrue($staff->check_passwd('legacy123'));
    }

    public function testCheckPasswdEmptyHash(): void
    {
        $this->setupStaffLookup(['passwd' => '']);
        $staff = new Staff(1);
        $this->assertFalse($staff->check_passwd('any'));
    }

    public function testIsActive(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->isactive());
    }

    public function testIsInactive(): void
    {
        $this->setupStaffLookup(['isactive' => 0]);
        $staff = new Staff(1);
        $this->assertFalse($staff->isactive());
    }

    public function testIsAdmin(): void
    {
        $this->setupStaffLookup(['isadmin' => 1]);
        $staff = new Staff(1);
        $this->assertTrue($staff->isadmin());
    }

    public function testIsNotAdmin(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertFalse($staff->isadmin());
    }

    public function testIsVisible(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->isVisible());
    }

    public function testOnVacation(): void
    {
        $this->setupStaffLookup(['onvacation' => 1]);
        $staff = new Staff(1);
        $this->assertTrue($staff->onVacation());
    }

    public function testIsAvailable(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->isAvailable());
    }

    public function testIsNotAvailableOnVacation(): void
    {
        $this->setupStaffLookup(['onvacation' => 1]);
        $staff = new Staff(1);
        $this->assertFalse($staff->isAvailable());
    }

    public function testIsNotAvailableInactive(): void
    {
        $this->setupStaffLookup(['isactive' => 0]);
        $staff = new Staff(1);
        $this->assertFalse($staff->isAvailable());
    }

    public function testIsNotAvailableGroupDisabled(): void
    {
        $this->setupStaffLookup(['group_enabled' => 0]);
        $staff = new Staff(1);
        $this->assertFalse($staff->isAvailable());
    }

    public function testIsStaff(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->isStaff());
    }

    public function testIsGroupActive(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->isGroupActive());
    }

    public function testCanCreateTickets(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->canCreateTickets());
    }

    public function testCanEditTickets(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->canEditTickets());
    }

    public function testCanDeleteTickets(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertFalse($staff->canDeleteTickets());
    }

    public function testCanCloseTickets(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->canCloseTickets());
    }

    public function testCanTransferTickets(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertFalse($staff->canTransferTickets());
    }

    public function testCanManageBanList(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertFalse($staff->canManageBanList());
    }

    public function testCanManageKb(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertFalse($staff->canManageKb());
    }

    public function testAdminCanDoEverything(): void
    {
        $this->setupStaffLookup(['isadmin' => 1]);
        $staff = new Staff(1);
        $this->assertTrue($staff->canCreateTickets());
        $this->assertTrue($staff->canEditTickets());
        $this->assertTrue($staff->canDeleteTickets());
        $this->assertTrue($staff->canCloseTickets());
        $this->assertTrue($staff->canTransferTickets());
        $this->assertTrue($staff->canManageBanList());
        $this->assertTrue($staff->canManageKb());
    }

    public function testCanAccessDeptOwn(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->canAccessDept(1));
    }

    public function testCanAccessDeptFromGroup(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->canAccessDept(2));
    }

    public function testCannotAccessForeignDept(): void
    {
        $this->setupStaffLookup(['dept_access' => '1']);
        $staff = new Staff(1);
        $this->assertFalse($staff->canAccessDept(99));
    }

    public function testGetDepts(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $depts = $staff->getDepts();
        $this->assertContains('1', $depts);
        $this->assertContains('2', $depts);
        $this->assertContains('3', $depts);
    }

    public function testForcePasswdChange(): void
    {
        $this->setupStaffLookup(['change_passwd' => 1]);
        $staff = new Staff(1);
        $this->assertTrue($staff->forcePasswdChange());
    }

    public function testObserveDaylight(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertTrue($staff->observeDaylight());
    }

    public function testGetRefreshRate(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $this->assertEquals(30, $staff->getRefreshRate());
    }

    public function testGetInfo(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $info = $staff->getInfo();
        $this->assertIsArray($info);
        $this->assertEquals('jdoe', $info['username']);
    }

    public function testGetData(): void
    {
        $this->setupStaffLookup();
        $staff = new Staff(1);
        $data = $staff->getData();
        $this->assertIsArray($data);
    }

    public function testCanManageTickets(): void
    {
        $this->setupStaffLookup(['can_close_tickets' => 1]);
        $staff = new Staff(1);
        $this->assertTrue($staff->canManageTickets());
    }

    public function testCannotManageTickets(): void
    {
        $this->setupStaffLookup([
            'can_delete_tickets' => 0,
            'can_close_tickets' => 0,
            'can_ban_emails' => 0,
            'isadmin' => 0,
        ]);
        DatabaseMock::setQueryResult('SELECT * FROM ' . DEPT_TABLE, []);
        $staff = new Staff(1);
        $this->assertFalse($staff->canManageTickets());
    }

    public function testCreateValidationNoName(): void
    {
        $errors = [];
        $result = Staff::create([
            'username' => 'test',
            'email' => 'test@test.com',
            'dept_id' => 1,
            'group_id' => 1,
            'npassword' => 'test123',
            'vpassword' => 'test123',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testCreateValidationNoUsername(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'test@test.com',
            'dept_id' => 1,
            'group_id' => 1,
            'npassword' => 'test123',
            'vpassword' => 'test123',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('username', $errors);
    }

    public function testCreateValidationShortUsername(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'ab',
            'email' => 'test@test.com',
            'dept_id' => 1,
            'group_id' => 1,
            'npassword' => 'test123',
            'vpassword' => 'test123',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('username', $errors);
    }

    public function testCreateValidationNoEmail(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'jdoe',
            'dept_id' => 1,
            'group_id' => 1,
            'npassword' => 'test123',
            'vpassword' => 'test123',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testCreateValidationNoDept(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'jdoe',
            'email' => 'test@test.com',
            'group_id' => 1,
            'npassword' => 'test123',
            'vpassword' => 'test123',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('dept', $errors);
    }

    public function testCreateValidationNoGroup(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'jdoe',
            'email' => 'test@test.com',
            'dept_id' => 1,
            'npassword' => 'test123',
            'vpassword' => 'test123',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('group', $errors);
    }

    public function testCreateValidationPasswordMismatch(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'jdoe',
            'email' => 'test@test.com',
            'dept_id' => 1,
            'group_id' => 1,
            'npassword' => 'test123',
            'vpassword' => 'different',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('vpassword', $errors);
    }

    public function testCreateValidationShortPassword(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'jdoe',
            'email' => 'test@test.com',
            'dept_id' => 1,
            'group_id' => 1,
            'npassword' => '12345',
            'vpassword' => '12345',
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('npassword', $errors);
    }

    public function testCreateValidationNoPasswordForNew(): void
    {
        $errors = [];
        $result = Staff::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'jdoe',
            'email' => 'test@test.com',
            'dept_id' => 1,
            'group_id' => 1,
        ], $errors);
        $this->assertFalse($result);
        $this->assertArrayHasKey('npassword', $errors);
    }
}

<?php

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testIsEmailValidWithCorrectEmails(): void
    {
        $this->assertNotEmpty(Validator::is_email('user@example.com'));
        $this->assertNotEmpty(Validator::is_email('test.user@domain.org'));
        $this->assertNotEmpty(Validator::is_email('user+tag@example.co.uk'));
        $this->assertNotEmpty(Validator::is_email('name123@test.io'));
    }

    public function testIsEmailInvalidWithBadEmails(): void
    {
        $this->assertEmpty(Validator::is_email(''));
        $this->assertEmpty(Validator::is_email('notanemail'));
        $this->assertEmpty(Validator::is_email('@domain.com'));
        $this->assertEmpty(Validator::is_email('user@'));
        $this->assertEmpty(Validator::is_email('user@.com'));
    }

    public function testIsPhoneValidWithCorrectNumbers(): void
    {
        $this->assertTrue(Validator::is_phone('1234567'));
        $this->assertTrue(Validator::is_phone('123-456-7890'));
        $this->assertTrue(Validator::is_phone('+1 (234) 567-8901'));
        $this->assertTrue(Validator::is_phone('12345678901234'));
    }

    public function testIsPhoneInvalidWithBadNumbers(): void
    {
        $this->assertFalse(Validator::is_phone('123'));
        $this->assertFalse(Validator::is_phone('abcdefgh'));
        $this->assertFalse(Validator::is_phone('12345678901234567'));
    }

    public function testIsUrlInvalidWithBadUrls(): void
    {
        $this->assertFalse(Validator::is_url(''));
        $this->assertFalse(Validator::is_url('notaurl'));
        $this->assertFalse(Validator::is_url('ftp://example.com'));
        $this->assertFalse(Validator::is_url('://missing-scheme.com'));
    }

    public function testIsIpValidWithCorrectIps(): void
    {
        $this->assertTrue(Validator::is_ip('192.168.1.1'));
        $this->assertTrue(Validator::is_ip('10.0.0.1'));
        $this->assertTrue(Validator::is_ip('255.255.255.255'));
        $this->assertTrue(Validator::is_ip('0.0.0.0'));
    }

    public function testIsIpInvalidWithBadIps(): void
    {
        $this->assertFalse(Validator::is_ip(''));
        $this->assertFalse(Validator::is_ip('999.999.999.999'));
        $this->assertFalse(Validator::is_ip('abc.def.ghi.jkl'));
    }

    public function testValidateRequiredStringField(): void
    {
        $fields = [
            'name' => ['type' => 'string', 'required' => 1, 'error' => 'Name required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['name' => 'John']));
        $this->assertFalse($this->validator->validate(['name' => '']));
        $this->assertFalse($this->validator->validate([]));
    }

    public function testValidateOptionalField(): void
    {
        $fields = [
            'phone' => ['type' => 'phone', 'required' => 0, 'error' => 'Bad phone']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['phone' => '']));
        $this->assertTrue($this->validator->validate(['phone' => '1234567890']));
    }

    public function testValidateIntegerType(): void
    {
        $fields = [
            'count' => ['type' => 'int', 'required' => 1, 'error' => 'Number required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['count' => '42']));
        $this->assertTrue($this->validator->validate(['count' => 0]));
        $this->assertFalse($this->validator->validate(['count' => 'abc']));
    }

    public function testValidateEmailType(): void
    {
        $fields = [
            'email' => ['type' => 'email', 'required' => 1, 'error' => 'Email required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['email' => 'test@example.com']));
        $this->assertFalse($this->validator->validate(['email' => 'invalid']));
    }

    public function testValidateDateType(): void
    {
        $fields = [
            'date' => ['type' => 'date', 'required' => 1, 'error' => 'Date required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['date' => '2026-01-15']));
        $this->assertTrue($this->validator->validate(['date' => '01/15/2026']));
        $this->assertFalse($this->validator->validate(['date' => 'not-a-date']));
    }

    public function testValidatePasswordType(): void
    {
        $fields = [
            'pass' => ['type' => 'password', 'required' => 1, 'error' => 'Password required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['pass' => 'SecurePassword123']));
        $this->assertFalse($this->validator->validate(['pass' => 'abc']));
    }

    public function testValidateUsernameType(): void
    {
        $fields = [
            'user' => ['type' => 'username', 'required' => 1, 'error' => 'Username required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['user' => 'admin']));
        $this->assertFalse($this->validator->validate(['user' => 'ab']));
    }

    public function testValidateZipcodeType(): void
    {
        $fields = [
            'zip' => ['type' => 'zipcode', 'required' => 1, 'error' => 'Zip required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['zip' => '12345']));
        $this->assertFalse($this->validator->validate(['zip' => '1234']));
        $this->assertFalse($this->validator->validate(['zip' => '123456']));
        $this->assertFalse($this->validator->validate(['zip' => 'abcde']));
    }

    public function testValidateMultipleFields(): void
    {
        $fields = [
            'name' => ['type' => 'string', 'required' => 1, 'error' => 'Name required'],
            'email' => ['type' => 'email', 'required' => 1, 'error' => 'Email required'],
            'phone' => ['type' => 'phone', 'required' => 0, 'error' => 'Invalid phone'],
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate([
            'name' => 'John',
            'email' => 'john@test.com',
        ]));

        $this->assertFalse($this->validator->validate([
            'name' => '',
            'email' => 'invalid',
        ]));

        $errors = $this->validator->errors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidateReturnsFalseWithInvalidSource(): void
    {
        $fields = [
            'name' => ['type' => 'string', 'required' => 1, 'error' => 'Name required']
        ];
        $this->validator->setFields($fields);

        $this->assertFalse($this->validator->validate(null));
        $this->assertFalse($this->validator->validate(''));
    }

    public function testValidateReturnsFalseWithNoFields(): void
    {
        $this->assertFalse($this->validator->validate(['name' => 'test']));
    }

    public function testIserrorMethod(): void
    {
        $fields = [
            'name' => ['type' => 'string', 'required' => 1, 'error' => 'Name required']
        ];
        $this->validator->setFields($fields);
        $this->validator->validate(['name' => '']);

        $this->assertTrue($this->validator->iserror());
    }

    public function testErrorsMethod(): void
    {
        $fields = [
            'name' => ['type' => 'string', 'required' => 1, 'error' => 'Name required']
        ];
        $this->validator->setFields($fields);
        $this->validator->validate(['name' => '']);

        $errors = $this->validator->errors();
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('Name required', $errors['name']);
    }

    public function testSetFieldsReturnsFalseForNonArray(): void
    {
        $val = 'not-array';
        $this->assertFalse($this->validator->setFields($val));
    }

    public function testValidateDoubleType(): void
    {
        $fields = [
            'amount' => ['type' => 'double', 'required' => 1, 'error' => 'Amount required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['amount' => '19.99']));
        $this->assertFalse($this->validator->validate(['amount' => 'abc']));
    }

    public function testValidateArrayType(): void
    {
        $fields = [
            'items' => ['type' => 'array', 'required' => 1, 'error' => 'Items required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['items' => [1, 2, 3]]));
        $this->assertFalse($this->validator->validate(['items' => 'not-array']));
    }

    public function testValidateRadioType(): void
    {
        $fields = [
            'choice' => ['type' => 'radio', 'required' => 1, 'error' => 'Choice required']
        ];
        $this->validator->setFields($fields);

        $this->assertTrue($this->validator->validate(['choice' => 'option1']));
    }

    public function testValidateUrlType(): void
    {
        $fields = [
            'website' => ['type' => 'url', 'required' => 1, 'error' => 'URL required']
        ];
        $this->validator->setFields($fields);

        $this->assertFalse($this->validator->validate(['website' => 'not-url']));
    }
}

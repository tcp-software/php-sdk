<?php

// require the ShiftPlanning SDK class
require('../src/shiftplanning.php');

/* set the developer key on class initialization */
$shiftplanning = new Shiftplanning(
	array(
		'key' => 'XXXXXXXXXXXXXXXXXX' // enter your developer key
	)
);

// or set the developer key using the setAppKey method
//$shiftplanning->setAppKey('XXXXXXXXXXXXXXXXXX');

/**
 *
 * Example SDK Usage
 * See Also: ShiftPlanning.SDK-tests.php
 *
 */

// check for a current active session
// if a session exists, $session will now hold the user's information
$session = $shiftplanning->getSession();

echo "appKey: " . $shiftplanning->getAppKey( ) . "<br/>";		// returns the developer key currently set
echo "appToken: " . $shiftplanning->getAppToken( ) . "<br/>";	// returns the token for the current session, error if not yet set

if (!$session)
{
	// if a session hasn't been started, create one

	// perform a single API call to authenticate a user
	$response = $shiftplanning->doLogin(
		array(// these fields are required to login
			'username' => 'jeffmarrone@qxdesigns.net',
			'password' => 'dev1234',
		)
	);

	if ($response['status']['code'] == 1)
	{// check to make sure that login was successful
		$session = $shiftplanning->getSession( );	// return the session data after successful login
		echo "Hi, " . $session['employee']['name'] . "<br/>";
	}
	else
	{// display the login error to the user
		echo $response['status']['text'] . "--" . $response['status']['error'];
	}
}
else
{
	// session has been established

	// the $session variable now holds the currently logged in user's data
	echo "Hi, " . $session['employee']['name'] . "<br/>";

	/**
	 * Quick Access ShiftPlanning SDK Methods:
	 * doLogin($array_of_user_data)
	 * doLogout()
	 * getMessages()
	 * getMessageDetails($message_id)
	 * createMessage($array_of_message_data)
	 * deleteMessage($message_id)
	 * getWallMessages()
	 * createWallMessage($array_of_message_data)
	 * deleteWallMessage($message_id, $array_of_other_message_data)
	 * getEmployees()
	 * getEmployeeDetails($employee_id_number)
	 * updateEmployee($employee_id, $array_of_updated_employee_data)
	 * createEmployee($array_of_employee_data)
	 * deleteEmployee($employee_id)
	 * getStaffSkills()
	 * getStaffSkillDetails($skill_id)
	 * createStaffSkill($array_of_skill_data)
	 * updateStaffSkill($skill_id, $array_of_skill_data)
	 * deleteStaffSkill($skill_id)
	 * createPing($array_of_ping_data)
	 * getSchedules()
	 * getScheduleDetails($schedule_id)
	 * createSchedule($array_of_schedule_data)
	 * updateSchedule($schedule_id, $array_of_schedule_data)
	 * deleteSchedule($schedule_id)
	 * getShifts()
	 * getShiftDetails($shift_id)
	 * updateShift($shift_id, $array_of_shift_data)
	 * createShift($array_of_shift_data)
	 * deleteShift($shift_id)
	 * getVacationSchedules($time_period_array)	// e.g. getVacationSchedules(array('start' => '', 'end' => ''));
	 * getVacationScheduleDetails($schedule_id)
	 * createVacationSchedule($array_of_schedule_data)
	 * updateVacationSchedule($schedule_id, $array_of_schedule_data)
	 * deleteVacationSchedule($schedule_id)
	 * getScheduleConflicts()
	 * getAdminSettings()
	 * updateAdminSettings($array_of_new_settings)
	 * getAdminFiles()
	 * getAdminFileDetails($file_id)
	 * updateAdminFile($file_id, $array_of_file_data)
	 * createAdminFile($array_of_file_data)
	 * deleteAdminFile($file_id)
	 * getAdminBackups()
	 * getAdminBackupDetails($backup_id)
	 * createAdminBackup($array_of_backup_data)
	 * deleteAdminBackup($backup_id)
	 * getAPIConfig()
	 * getAPIMethods()
	 */

	/**
	 * All Quick-Access methods return a response like this:
	 * array(
	 * 	'status' => array('code' => '1', 'text' => 'Success', 'error' => 'Error message if any'),
	 * 	'data' => array(
	 *		'field_name' => 'value'
	 * 		)
	 * 	)
	 *
	 * For methods that return multiple objects (as in the case for the getMessages() method
	 * responses will look like this, where the indexes [0], [1] would be replaced with the
	 * message you're looking to display
	 *
	 * array(
	 * 	'status' => array('code' => '1', 'text' => 'Success', 'error' => 'Error message if any'),
	 * 	'data' => array(
	 *		[0] => array (
	 *				'id' => 1,
	 *				'name' => 'value'
	 *			)
	 *		[1] => array (
	 *				'id' => 2,
	 *				'name' => 'value'
	 *			)
	 * 		)
	 * 	)
	 */

	// Quick-Access Methods, to perform API calls more easily
	$employees = $shiftplanning->getEmployees();	// returns all employees
	$employee_1_wage = $employees['data'][0]['wage'];
	echo "Employee 1 Wage: $" . $employee_1_wage . "<br/>";
	echo "Employee 1 Nick Name: " . $employees['data'][0]['nick_name'] . "<br/>";

	$update_employee_record_response = $shiftplanning->updateEmployee(	// update employee record using Quick-Access method
		$employees['data'][0]['id'],
		array(
			'wage' => 75.00,
			'nick_name' => 'Jeff'
		)
	);

	if ($update_employee_record_response['status']['code'] == 1)
	{// employee update successful
		echo "Employee record updated.<br/>";
	}
	else
	{// error updating employee
		echo $update_employee_record_response['status']['text'] . "<br/>";
	}

	// perform multiple API calls in one request
	// you can make up to 5 api calls per request
	// make sure each call is its own array, and all calls are wrapped in an array
	$response = $shiftplanning->setRequest(array(// array wrapper
		array(// first api call (index=0)
			'module' => 'messaging.message',
			'method' => 'CREATE',
			'subject' => 'Test Message',
			'message' => 'This is a brand new test',
			'to' => $session['employee']['id']
		),
		array(// second api call (index=1)
			'module' => 'messaging.messages',
			'method' => 'GET',
		),
		array(// third api call (index=2)
			'module' => 'schedule.schedules',
			'method' => 'GET'
		),
		array(// fourth api call (index=3)
			'module' => 'admin.settings',
			'method' => 'GET'
		)
	));

	$send_message = $shiftplanning->getResponse(0);	// returns the response/data for the first api call (index=0)
	$get_messages = $shiftplanning->getResponse(1);	// returns the response/data for the second api call (index=1)
	$get_schedules = $shiftplanning->getResponse(2);	// returns the response/data for the third api call (index=2)
	$get_settings = $shiftplanning->getResponse(3);	// returns the response/data for the fourth api call (index=3)

	echo "Send Message Response: " . $send_message['status']['text'] . "<br/>";
	echo "Get Messages Response: " . $get_messages['status']['text'] . "<br/>";
	echo "Get Schedules Response: " . $get_schedules['status']['text'] . "<br/>";
	echo "Get Admin Settings Response: " . $get_settings['status']['text'] . "<br/>";

	if( $get_messages['status']['code'] == 1 )
	{// messages were retrieved successfully

		if( count( $get_messages['data'] ) > 0 )
		{// more than zero messages

			echo "First Message, Message From: " . $get_messages['data'][0]['from']['name'] . "<br/>";
			if( $get_messages['data'][1] )
			{//
				echo "Second Message, Message From: " . $get_messages['data'][1]['from']['name'] . "<br/>";
			}
			print_r( $get_messages['data'][0] );	// replace "0" with the index of the message you want to display

			foreach( $get_messages['data'] as $number => $message )
			{// loop through each message that was returned and delete it
				// use Quick-Access deleteMessage() method
				$delete_message_response = $shiftplanning->deleteMessage(  $message['id'] );
				echo "Delete Message ID {$message['id']} Response: " . $delete_message_response['status']['text'] . "<br/>";
			}
		}

	}

	// schedules were retrieved successfully
	if ($get_schedules['status']['code'] == 1)
	{
		print_r($get_schedules['data'][0]);	// print data from first schedule returned
	}

	// admin settings retrieved successfully
	if ($get_settings['status']['code'] == 1)
	{
		print_r($get_settings);
	}

	$shiftplanning->doLogout();
}
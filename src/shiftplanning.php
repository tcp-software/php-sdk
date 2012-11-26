<?php
/**
 * ShiftPlanning PHP SDK
 * Version: 1.0
 * Date: 11/01/2010
 * http://www.shiftplanning.com/api/
 */

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
 * getVacationSchedules($time_period_array)	// e.g. getVacationSchedules( array( 'start' => '', 'end' => ''));
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
 * 	'status' => array( 'code' => '1', 'text' => 'Success', 'error' => 'Error message if any'),
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
 * 	'status' => array( 'code' => '1', 'text' => 'Success', 'error' => 'Error message if any'),
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

class Shiftplanning
{
	private $_key;

	private $_callback;

	private $_init;

	/**
	 * @var  boolean  In debug mode
	 */
	private $_debug = FALSE;

	/**
	 * @var  array
	 */
	private $request = array();

	/**
	 * @var  array
	 */
	private $requests = array();

	/**
	 * @var  array
	 */
	private $response = array();

	/**
	 * @var  array
	 */
	private $raw_response = array();

	/**
	 * @var  array cached storage of internal error messages
	 */
	private $internal_errors = array();

	/**
	 * @var  array cached storage of response messages
	 */
	private $response_messages = array();


	// constants
	const SESSION_IDENTIFIER = 'SP';
	const API_ENDPOINT = 'http://www.shiftplanning.com/api/';
	const OUTPUT_TYPE = 'json';


	/**
	 * Construct the SDK
	 * @param array $config [description]
	 */
	public function __construct($config = array())
	{
		try
		{
			$this->startSession();
			// set the developer key
			$this->setAppKey($config['key']);

			if (!function_exists( 'curl_init'))
			{
				// curl is not available
				throw new Exception($this->internal_errors(6));
			}

			if (!function_exists( 'json_decode'))
			{
				// json_decode is not available
				throw new Exception($this->internal_errors(7));
			}
		}
		catch( Exception $e)
		{
			echo $e->getMessage(); exit;
		}
	}

	/**
	 * Turn debug on
	 * @param boolean $switch [description]
	 */
	public function setDebug($switch = FALSE)
	{
		// delete previous log file
		if (file_exists('log.txt'))
		{
			unlink( 'log.txt');
		}
		$this->_debug = TRUE;
	}

	// start the session
	protected function startSession()
	{
		session_name( self::SESSION_IDENTIFIER);
		session_start();
	}

	// store the user data to this session
	private function setSession()
	{
		$_SESSION['token'] = $this->response['token'][0];
		$_SESSION['data'] = $this->response['data'][0];
	}

	// destroy the currently active session
	private function destroySession()
	{
		$logout = $this->setRequest( array (
			'module' => 'staff.logout',
			'method' => 'GET'
		));

		// logout successful, remove local session data
		if ($logout['status']['code'] == 1)
		{
			unset($_SESSION['token']);
			unset($_SESSION['data']);
			
			# REMOVE COOKIE
			setcookie("mobile_token", $this->response['token'][0], time()-(30*86400), "/", ".shiftplanning.com");
		}
		return $logout;
	}

	/**
	 * Check whether a valid session has been established
	 * @return [type] [description]
	 */
	public function getSession()
	{
		// user is already authenticated
		if (isset($_SESSION['token']))
		{
			return $_SESSION['data'];
		}
		else
		{
			// user has not authenticated
			return FALSE;
		}
	}

	/**
	 * Set the method to call after successful api call
	 * @param [type] $callback [description]
	 */
	private function setCallback($callback)
	{ 
		$this->_callback = $callback;
		return $this->_callback;
	}

	/**
	 * Return the raw response data
	 * @return [type] [description]
	 */
	public function getRawResponse()
	{
		return $this->raw_response;
	}

	/**
	 * Return the developer key
	 * @return [type] [description]
	 */
	public function getAppKey()
	{
		return $this->_key;
	}

	/**
	 * Set the developer key to use
	 * @param [type] $key [description]
	 */
	public function setAppKey($key)
	{
		$this->_key = $key;
		return $this->_key;
	}

	/**
	 * Get the token that's currently being used
	 * @return [type] [description]
	 */
	public function getAppToken()
	{
		try
		{
			// user authenticated, return the token
			if ($this->getSession())
			{
				return $_SESSION['token'];
			}
			else
			{
				// user not authenticated, return an error
				throw new Exception($this->internal_errors(4));
			}
		}
		catch( Exception $e)
		{
			echo $e->getMessage();
		}
	}

	/**
	 * Set the request parameters
	 * @param array $requests [description]
	 */
	public function setRequest($requests = array())
	{
		// clear out previous request data
		unset($this->requests);

		// set the default response type of JSON
		$this->request['output'] = self::OUTPUT_TYPE;

		$this->_init = 0;

		foreach($requests as $r => $v)
		{
			// loop through each request array
			if (is_array($v))
			{
				$this->requests[] = $v;
			}
			else
			{
				// automatically initialize session after this API call
				if ($requests['module'] == 'staff.login')
				{
					$this->_init = 1;
				}
				$this->requests[] = $requests; break;
			}
		}

		return $this->api();
	}

	/**
	 * Return the request parameters
	 * @return [type] [description]
	 */
	public function getRequest()
	{
		return array_merge($this->request, array( 'request' => $this->requests));
	}

	/**
	 * Set the response data
	 * @param [type] $response [description]
	 */
	private function setResponse($response)
	{
		// remove previous response data
		unset($this->response);
		// set new response data
		if (! isset($response[0]) || !is_array($response[0]))
		{
			$this->response['response'][0] = array(
				'code' => $response['status'],
				'text' => $this->getResponseText($response['status']),
				'error' => (isset($response['error']) ? $response['error'] : '')
			);
			$this->response['data'][0] = $response['data'];
			$this->response['token'][0] = $response['token'];
		}
		else
		{
			// loop through each response
			foreach($response as $num => $data)
			{

				$this->response['response'][$num] = array(
					'code' => $data['status'],
					'text' => $this->getResponseText($data['status']),
					'error' => (isset($data['error']) ? $data['error'] : '')
				);
				$tmp = array();
				$id = 0;

				// is there an array returned
				if (is_array($data['data']))
				{
					foreach($data['data'] as $n => $v)
					{
						if (is_array($v))
						{
							foreach($v as $key => $val)
							{
								$tmp[$n][$key] = $val;
							}
						}
						else
						{
							$tmp[$n] = $v;
						}
					}
					$id++;
					$this->response['data'][$num] = $tmp;
				}
				else
				{
					// the data response is text
					$this->response['data'][$num] = $data['data'];
				}
			}
		}
	}

	/**
	 * Get the API response data to the calling method
	 * @param  integer $call_num Calling method number
	 * @return array             API Response Data
	 */
	public function getResponse($call_num = 0)
	{
		return array(
			'status' => $this->response['response'][$call_num],
			'data' => $this->response['data'][$call_num],
			'error' => (isset($this->response['error'])) ? $this->response['error'][$call_num]: NULL,
		);
	}

	/**
	 * Get a reason text for a response code
	 * @param  int $code [description]
	 * @return string     Response
	 */
	private function getResponseText(&$code)
	{
		// Check internal cache or get array from file
		if (empty($this->response_messages))
		{
			// Load response messages array
			$response_messages = include 'messages/response.php';
		}
		else
		{
			$response_messages = $this->response_messages;
		}

		// select a response code to display
		if (array_key_exists($code, $response_messages))
		{
			$message = $response_messages[$code];
		}
		else
		{
			$message = $response_messages[0];
		}

		return $message;
	}

	/**
	 * Errors internal to the ShiftPlanning SDK
	 * @param  [type] $errno [description]
	 * @return [type]        [description]
	 */
	private function internal_errors($errno = 0)
	{
		// Check internal cache or get array from file
		if (empty($this->internal_errors))
		{
			$internal_error_messages = include 'messages/internal_error.php';
		}
		else
		{
			$internal_error_messages = $this->internal_errors;
		}

		// internal error message
		if (array_key_exists($errno, $internal_error_messages))
		{
			$message = $internal_error_messages[$errno];
		}
		else
		{
			$message = $internal_error_messages[0];
		}
		
		return $message; exit;
	}

	/**
	 * Create the api call
	 * @return [type] [description]
	 */
	private function api()
	{
		if ($this->_callback == null)
		{
			// method to call after successful api request
			$this->setCallback( 'getResponse');
		}

		// session already established, use token
		if ($this->getSession())
		{
			// remove the developer key from the request, since it's not necessary
			unset($this->request['key']);
			// set the token for this request, since the user is already authenticated
			$this->request['token'] = $_SESSION['token'];
		}
		else
		{
			// session has not been established, use developer key to access API
			try
			{
				if (isset($this->_key))
				{
					// developer key is set
					$this->request['key'] = $this->_key;
				}
				else
				{
					// developer key is not set
					throw new Exception($this->internal_errors(5));
				}
			}
			catch( Exception $e)
			{
				echo $e->getMessage();
			}
		}
		// make the api request
		return $this->perform_request();
	}

	/**
	 * Get the mime type by file extension
	 * @param  [type] $extension File extension without leading dot
	 * @return string            Mime Type
	 */
	private function getFileMimeType($extension)
	{
		try
		{	
			$mimes = include 'config/mimes.php';
			if ($mimes[$extension])
			{
				return $mimes[$extension];
			}
			else
			{
				throw new Exception('Mime for .' . $extension . ' not found');
			}
		}
		catch( Exception $e)
		{
			echo $e->getMessage();
		}
	}

	/**
	 * Get file details, (data, length, mimetype)
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	private function getFileData($file)
	{
		try
		{
			if (file_exists($file))
			{
				// file
				$file_data['filedata'] = file_get_contents($file);
				$file_data['filelength'] = strlen($file_data['filedata']);
				if (function_exists( 'mime_content_type'))
				{
					// mime_content_type function is available
					$file_data['mimetype'] = mime_content_type($file);
				}
				else
				{
					$parts = explode( '.', $file);
					$extension = strtolower($parts[ sizeOf($parts) - 1 ]);
					$file_data['mimetype'] = $this->getFileMimeType($extension);
				}

				return array(
					'filedata' => $file_data['filedata'],
					'filelength' => $file_data['filelength'],
					'mimetype' => $file_data['mimetype']
				);
			}
			else
			{
				throw new Exception($this->internal_errors(8));
			}
		}
		catch( Exception $e)
		{
			echo $e->getMessage(); exit;
		}
	}

	/**
	 * Perform the api request
	 * @return [type] [description]
	 */
	private function perform_request()
	{
		try
		{
			$ch = curl_init(self::API_ENDPOINT);

			$filedata = '';
			if (is_array($this->requests))
			{
				foreach($this->requests as $key => $request)
				{
					if (isset($request['filedata']))
					{
						$filedata = $request['filedata'];
						unset($this->requests[$key]['filedata']);
					}
				}
			}

			$post = $filedata ? array( 'data'=> json_encode($this->getRequest()),
				'filedata' => $filedata) : array( 'data' => json_encode($this->getRequest()));

			curl_setopt($ch, CURLOPT_URL, self::API_ENDPOINT);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

			// return the response from the api
			$response = curl_exec($ch);
			$http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// response from API was a successful
			if ($http_response_code == 200)
			{
				$temp = json_decode($response, TRUE);
				// decode the response and store each call response in its own array
				$this->setResponse($temp);

				// initialize a session
				if ($this->_init == 1)
				{
					$this->setSession();
				}

				// raw response call
				$this->raw_response = $temp;
				if ($this->_debug == TRUE)
				{
					// debug mode is on
					$request = json_encode($this->getRequest());
					$db = fopen('log.txt', 'a');
					$tmp_vals = array();
					if (is_array($this->response['data'][0]))
					{
						foreach($this->response['data'] as $n => $v)
						{
							foreach($v as $key => $val)
							{
								$tmp_vals[$n][$key] = $val;
							}
						}
					}
					else
					{
						foreach($this->response['data'] as $n => $v)
						{
							$tmp_vals[$n] = $v;
						}
					}
					fwrite($db, date('m-d-Y h:i a'). "\n" . 'REQUEST: ' . $request . "\n"
						. 'RESPONSE STATUS: (' . $this->response['response'][0]['code']
						. ') ' . $this->response['response'][0]['text'] . " -- " . $this->response['response'][0]['error'] . "\n"
						. 'RESPONSE DATA: ' . json_encode($tmp_vals) . "\n\n");
					fclose($db);
				}

				// perform the callback method
				return $this->{ $this->_callback }();
			}
			else
			{
				// response from API was unsuccessful
				throw new Exception($this->internal_errors(2));
			}
		}
		catch( Exception $e)
		{
			echo $e->getMessage();
		}
	}

	/**
	 * User Authentication Methods
	 */
	
	/**
	 * Perform a login api call
	 * @param  array  $user [description]
	 * @return [type]       [description]
	 */
	public function doLogin($user = array())
	{
		return $this->setRequest(
			array(
				'module' => 'staff.login',
				'method' => 'GET',
				'username' => $user['username'],
				'password' => $user['password']
			)
		);
	}

	/**
	 * Erase token and user data from current session
	 */
	public function doLogout()
	{
		$this->destroySession();
	}


	/**
	 * Message Methods
	 */
	
	/**
	 * Get messages for the currently logged in user
	 * @return [type] [description]
	 */
	public function getMessages()
	{
		return $this->setRequest(
			array(
				'module' => 'messaging.messages',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get message details for a specific message
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getMessageDetails($id)
	{
		return $this->setRequest(
			array(
				'module' => 'messaging.message',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Create a new message
	 * @param  array  $message [description]
	 * @return [type]          [description]
	 */
	public function createMessage($message = array())
	{
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'messaging.message',
					'method' => 'CREATE'
				),
				$message
			)
		);
	}

	/**
	 * Delete a message
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteMessage($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'messaging.message',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/**
	 * Get message wall
	 * @return [type] [description]
	 */
	public function getWallMessages()
	{ 
		return $this->setRequest(
			array(
				'module' => 'messaging.wall',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Create a wall message
	 * @param  array  $message [description]
	 * @return [type]          [description]
	 */
	public function createWallMessage($message = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'messaging.wall',
					'method' => 'CREATE'
				),
				$message
			)
		);
	}

	/**
	 * Delete a wall message
	 * @param  [type] $id      [description]
	 * @param  array  $details [description]
	 * @return [type]          [description]
	 */
	public function deleteWallMessage($id, $details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'messaging.wall',
					'method' => 'DELETE',
					'id' => $id
				),
				$details
			)
		);
	}

	/*
	 * Staff Methods
	 *
	 */
	

	/**
	 * Get a list of employees
	 * @return [type] [description]
	 */
	public function getEmployees()
	{ 
		return $this->setRequest(
			array(
				'module' => 'staff.employees',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get details for a specific employee
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getEmployeeDetails($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'staff.employee',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Update an employee record
	 * @param  [type] $id       [description]
	 * @param  array  $new_data [description]
	 * @return [type]           [description]
	 */
	public function updateEmployee($id, $new_data = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'staff.employee',
					'method' => 'UPDATE',
					'id' => $id
				),
				$new_data
			)
		);
	}

	/**
	 * Create a new employee record
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function createEmployee($data)
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'staff.employee',
					'method' => 'CREATE'
				),
				$data
			)
		);
	}

	/**
	 * Delete an employee
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteEmployee($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'staff.employee',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/**
	 * Get staff skills
	 * @return [type] [description]
	 */
	public function getStaffSkills()
	{ 
		return $this->setRequest(
			array(
				'module' => 'staff.skills',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get staff skill details
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getStaffSkillDetails($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'staff.skill',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Create staff skill
	 * @param  array  $skill_details [description]
	 * @return [type]                [description]
	 */
	public function createStaffSkill($skill_details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'staff.skill',
					'method' => 'CREATE'
				),
				$skill_details
			)
		);
	}

	/**
	 * Update staff skill
	 * @param  [type] $id            [description]
	 * @param  array  $skill_details [description]
	 * @return [type]                [description]
	 */
	public function updateStaffSkill($id, $skill_details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'staff.skill',
					'method' => 'UPDATE',
					'id' => $id
				),
				$skill_details
			)
		);
	}

	/**
	 * Delete staff skill
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteStaffSkill($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'staff.skill',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/**
	 * Create a ping
	 * @param  array  $ping_data [description]
	 * @return [type]            [description]
	 */
	public function createPing($ping_data = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'staff.ping',
					'method' => 'CREATE'
				),
				$ping_data
			)
		);
	}

	/**
	 * Schedule Methods
	 */
	
	/**
	 * Get schedules
	 * @return [type] [description]
	 */
	public function getSchedules()
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.schedules',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get schedule details
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getScheduleDetails($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.schedule',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Create a new schedule
	 * @param  array  $schedule_details [description]
	 * @return [type]                   [description]
	 */
	public function createSchedule($schedule_details = array())
	{
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.schedule',
					'method' => 'CREATE'
				),
				$schedule_details
			)
		);
	}

	/**
	 * Update an existing schedule
	 * @param  [type] $id               [description]
	 * @param  array  $schedule_details [description]
	 * @return [type]                   [description]
	 */
	public function updateSchedule($id, $schedule_details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.schedule',
					'method' => 'UPDATE',
					'id' => $id
				),
				$schedule_details
			)
		);
	}

	/**
	 * Delete an existing schedule
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteSchedule($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.schedule',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/**
	 * Get shifts
	 * @return [type] [description]
	 */
	public function getShifts()
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.shifts',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get shift details
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getShiftDetails($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.shift',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Update shift details
	 * @param  [type] $id            [description]
	 * @param  array  $shift_details [description]
	 * @return [type]                [description]
	 */
	public function updateShift($id, $shift_details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.shift',
					'method' => 'UPDATE',
					'id' => $id
				),
				$shift_details
			)
		);
	}

	/**
	 * Create a new shift
	 * @param  array  $shift_details [description]
	 * @return [type]                [description]
	 */
	public function createShift($shift_details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.shift',
					'method' => 'CREATE'
				),
				$shift_details
			)
		);
	}

	/**
	 * Delete a shift
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteShift($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.shift',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/**
	 * Get schedule vacations, pass start and end params to get vacations within a certian time-period
	 * @param  array  $time_period [description]
	 * @return [type]              [description]
	 */
	public function getVacationSchedules($time_period = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.vacations',
					'method' => 'GET'
				),
				$time_period
			)
		);
	}

	/**
	 * Get vacation schedule details
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getVacationScheduleDetails($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.vacation',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Create a vacation schedule
	 * @param  array  $vacation_details [description]
	 * @return [type]                   [description]
	 */
	public function createVacationSchedule($vacation_details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.vacation',
					'method' => 'CREATE'
				),
				$vacation_details
			)
		);
	}

	/**
	 * Update a vacation schedule
	 * @param  [type] $id               [description]
	 * @param  array  $vacation_details [description]
	 * @return [type]                   [description]
	 */
	public function updateVacationSchedule($id, $vacation_details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.vacation',
					'method' => 'UPDATE',
					'id' => $id
				),
				$vacation_details
			)
		);
	}

	/**
	 * Delete a vacation schedule
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteVacationSchedule($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'schedule.vacation',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/**
	 * Get schedule conflicts
	 * @param  array  $time_period [description]
	 * @return [type]              [description]
	 */
	public function getScheduleConflicts($time_period = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'schedule.conflicts',
					'method' => 'GET'
				),
				$time_period
			)
		);
	}

	/*
	 * Administration Methods
	 *
	 */
	
	/**
	 * Get admin settings
	 * @return [type] [description]
	 */
	public function getAdminSettings()
	{ 
		return $this->setRequest(
			array(
				'module' => 'admin.settings',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Update admin settings
	 * @param  array  $settings [description]
	 * @return [type]           [description]
	 */
	public function updateAdminSettings($settings = array())
	{
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'admin.settings',
					'method' => 'UPDATE'
				),
				$settings
			)
		);
	}

	/**
	 * Get administrator file listing
	 * @return [type] [description]
	 */
	public function getAdminFiles()
	{
		return $this->setRequest(
			array(
				'module' => 'admin.files',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get admin file details
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getAdminFileDetails($id)
	{
		return $this->setRequest(
			array(
				'module' => 'admin.file',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Update admin file details
	 * @param  [type] $id      [description]
	 * @param  array  $details [description]
	 * @return [type]          [description]
	 */
	public function updateAdminFile($id, $details = array())
	{ 
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'admin.file',
					'method' => 'UPDATE',
					'id' => $id
				),
				$details
			)
		);
	}

	/**
	 * Create new admin file
	 * @param  array  $file_details [description]
	 * @return [type]               [description]
	 */
	public function createAdminFile($file_details = array())
	{ 
		$file_details = array_merge($file_details, $this->getFileData($file_details['filename']));
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'admin.file',
					'method' => 'CREATE'
				),
				$file_details
			)
		);
	}

	/**
	 * Delete admin file
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteAdminFile($id)
	{
		return $this->setRequest(
			array(
				'module' => 'admin.file',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/**
	 * Get admin backups
	 * @return [type] [description]
	 */
	public function getAdminBackups()
	{
		return $this->setRequest(
			array(
				'module' => 'admin.backups',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get admin backup details
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getAdminBackupDetails($id)
	{
		return $this->setRequest(
			array(
				'module' => 'admin.backup',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	/**
	 * Create an admin backup
	 * @param  array  $backup_details [description]
	 * @return [type]                 [description]
	 */
	public function createAdminBackup($backup_details = array() )
	{ 
		$backup_details = array_merge($backup_details, $this->getFileData($backup_details['filename']));
		return $this->setRequest(
			array_merge(
				array(
					'module' => 'admin.backup',
					'method' => 'CREATE'
				),
				$backup_details
			)
		);
	}

	/**
	 * Delete an admin backup
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function deleteAdminBackup($id)
	{ 
		return $this->setRequest(
			array(
				'module' => 'admin.backup',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	/*
	 * API Methods
	 *
	 */
	
	/**
	 * Get api config
	 * @return [type] [description]
	 */
	public function getAPIConfig()
	{ 
		return $this->setRequest(
			array(
				'module' => 'api.config',
				'method' => 'GET'
			)
		);
	}

	/**
	 * Get all available api methods
	 * @return [type] [description]
	 */
	public function getAPIMethods()
	{ 
		return $this->setRequest(
			array(
				'module' => 'api.methods',
				'method' => 'GET'
			)
		);
	}
}

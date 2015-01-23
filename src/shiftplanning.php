<?php
/**
 * ShiftPlanning PHP SDK
 * Version: 1.0
 * Date: 11/01/2010
 * http://www.shiftplanning.com/api/
 */

/**
 * Quick Access ShiftPlanning SDK Methods:
 * doLogin( $array_of_user_data )
 * doLogout( )
 * getMessages( )
 * getMessageDetails( $message_id )
 * createMessage( $array_of_message_data )
 * deleteMessage( $message_id )
 * getWallMessages( )
 * createWallMessage( $array_of_message_data )
 * deleteWallMessage( $message_id, $array_of_other_message_data )
 * getEmployees( )
 * getEmployeeDetails( $employee_id_number )
 * updateEmployee( $employee_id, $array_of_updated_employee_data )
 * createEmployee( $array_of_employee_data )
 * deleteEmployee( $employee_id )
 * getStaffSkills( )
 * getStaffSkillDetails( $skill_id )
 * createStaffSkill( $array_of_skill_data )
 * updateStaffSkill( $skill_id, $array_of_skill_data )
 * deleteStaffSkill( $skill_id )
 * createPing( $array_of_ping_data )
 * getSchedules( )
 * getScheduleDetails( $schedule_id )
 * createSchedule( $array_of_schedule_data )
 * updateSchedule( $schedule_id, $array_of_schedule_data )
 * deleteSchedule( $schedule_id )
 * getShifts( )
 * getShiftDetails( $shift_id )
 * updateShift( $shift_id, $array_of_shift_data )
 * createShift( $array_of_shift_data )
 * deleteShift( $shift_id )
 * getVacationSchedules( $time_period_array )	// e.g. getVacationSchedules( array( 'start' => '', 'end' => '' ) );
 * getVacationScheduleDetails( $schedule_id )
 * createVacationSchedule( $array_of_schedule_data )
 * updateVacationSchedule( $schedule_id, $array_of_schedule_data )
 * deleteVacationSchedule( $schedule_id )
 * getScheduleConflicts( )
 * getAdminSettings( )
 * updateAdminSettings( $array_of_new_settings )
 * getAdminFiles( )
 * getAdminFileDetails( $file_id )
 * updateAdminFile( $file_id, $array_of_file_data )
 * createAdminFile( $array_of_file_data )
 * deleteAdminFile( $file_id )
 * getAdminBackups( )
 * getAdminBackupDetails( $backup_id )
 * createAdminBackup( $array_of_backup_data )
 * deleteAdminBackup( $backup_id )
 * getAPIConfig( )
 * getAPIMethods( )
 */

/**
 * All Quick-Access methods return a response like this:
 * array(
 * 	'status' => array( 'code' => '1', 'text' => 'Success', 'error' => 'Error message if any' ),
 * 	'data' => array(
 *		'field_name' => 'value'
 * 		)
 * 	)
 *
 * For methods that return multiple objects (as in the case for the getMessages( ) method
 * responses will look like this, where the indexes [0], [1] would be replaced with the
 * message you're looking to display
 *
 * array(
 * 	'status' => array( 'code' => '1', 'text' => 'Success', 'error' => 'Error message if any' ),
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

class shiftplanning
{//
	private $_key;
	private $_callback;
	private $_init;
	private $_debug;
	private $request = array( );
	private $requests = array( );
	private $response = array( );
	private $raw_response = array( );

	// constants
	const session_identifier = 'SP';
	const api_endpoint = 'https://www.shiftplanning.com/api/';
	const output_type = 'json';

	public function __construct( $config = array() )
	{// construct the SDK
		try
		{//
			$this->_debug = false;
			$this->startSession( );
			// set the developer key
			$this->setAppKey( $config['key'] );

			if( !function_exists( 'curl_init' ) )
			{// curl is not available
				throw new Exception( $this->internal_errors( 6 ) );
			}
			if( !function_exists( 'json_decode' ) )
			{// json_decode is not available
				throw new Exception( $this->internal_errors( 7 ) );
			}
		}
		catch( Exception $e )
		{//
			echo $e->getMessage( ); exit;
		}
	}

	public function setDebug( $switch = false )
	{// turn debug on
		if( file_exists('log.txt') )
		{// delete previous log file
			unlink( 'log.txt' );
		}
		$this->_debug = true;
	}

	protected function startSession( )
	{// start the session
		session_name( self::session_identifier );
		session_start( );
	}

	private function setSession( )
	{// store the user data to this session
		$_SESSION['token'] = $this->response['token'][0];
		$_SESSION['data'] = $this->response['data'][0];
	}

	private function destroySession( )
	{// destroy the currently active session
		$logout = $this->setRequest( array (
			'module' => 'staff.logout',
			'method' => 'GET'
		) );
		if( $logout['status']['code'] == 1 )
		{// logout successful, remove local session data
			unset( $_SESSION['token'] );
			unset( $_SESSION['data'] );
			
			# REMOVE COOKIE
			setcookie("mobile_token", $this->response['token'][0], time()-(30*86400), "/", ".shiftplanning.com");
		}
		return $logout;
	}

	public function getSession( )
	{// check whether a valid session has been established
		if( isset( $_SESSION['token'] ) )
		{// user is already authenticated
			return $_SESSION['data'];
		}
		else
		{// user has not authenticated
			return false;
		}
	}

	private function setCallback( $callback )
	{// set the method to call after successful api call
		$this->_callback = $callback;
		return $this->_callback;
	}

	public function getRawResponse( )
	{// return the raw response data
		return $this->raw_response;
	}

	public function getAppKey( )
	{// return the developer key
		return $this->_key;
	}

	public function setAppKey( $key )
	{// set the developer key to use
		$this->_key = $key;
		return $this->_key;
	}

	public function getAppToken( )
	{// return the token that's currently being used
		try
		{//
			if( $this->getSession( ) )
			{// user authenticated, return the token
				return $_SESSION['token'];
			}
			else
			{// user not authenticated, return an error
				throw new Exception( $this->internal_errors( 4 ) );
			}
		}
		catch( Exception $e )
		{//
			echo $e->getMessage();
		}
	}

	public function setRequest( $requests = array( ) )
	{// set the request parameters
		// clear out previous request data
		unset( $this->requests );

		// set the default response type of JSON
		$this->request['output'] = self::output_type;

		$this->_init = 0;

		foreach( $requests as $r => $v )
		{// loop through each request array
			if( is_array( $v ) )
			{//
				$this->requests[] = $v;
			}
			else
			{//
				if( $requests['module'] == 'staff.login' )
				{// automatically initialize session after this API call
					$this->_init = 1;
				}
				$this->requests[] = $requests; break;
			}
		}

		return $this->api( );
	}

	public function getRequest( )
	{// return the request parameters
		return array_merge( $this->request, array( 'request' => $this->requests ) );
	}

	private function setResponse( $response )
	{// set the response data
		// remove previous response data
		unset( $this->response );
		// set new response data
		if( isset($response['data']) )
		{//
			$this->response['response'][0] = array(
				'code' => $response['status'],
				'text' => $this->getResponseText( $response['status'] ),
				'error' => (isset($response['error']))?$response['error']:''
			);
			$this->response['data'][0] = $response['data'];
			$this->response['token'][0] = $response['token'];
		}
		else
		{//
			foreach( $response as $num => $data )
			{// loop through each response
				if( isset($data['status']) ){
					$this->response['response'][$num] = array(
						'code' => $data['status'],
						'text' => $this->getResponseText( $data['status'] ),
						'error' => (isset($data['error']))?$data['error']:''
					);
					$tmp = array( );
					$id = 0;
					if( is_array( $data['data'] ) )
					{// is there an array returned
						foreach( $data['data'] as $n => $v )
						{//
							if( is_array( $v ) )
							{//
								foreach( $v as $key => $val )
								{//
									$tmp[$n][$key] = $val;
								}
							}
							else
							{//
								$tmp[$n] = $v;
							}
						}
						$id++;
						$this->response['data'][$num] = $tmp;
					}
					else
					{// the data response is text
						$this->response['data'][$num] = $data['data'];
					}
				}
			}
		}
	}

	public function getResponse( $call_num = 0 )
	{// return the API response data to the calling method
		return array(
			'status' => $this->response['response'][$call_num],
			'data' => $this->response['data'][$call_num],
			'error' => (isset($response['error']))?$response['error'][$call_num]:''
		);
	}

	private function getResponseText( $code )
	{// return a reason text for a response code
		switch( $code )
		{// select a response code to display
			case '-3' : $reason = 'Flagged API Key - Pemanently Banned'; break;
			case '-2' : $reason = 'Flagged API Key - Too Many invalid access attempts - contact us'; break;
			case '-1' : $reason = 'Flagged API Key - Temporarily Disabled - contact us'; break;
			case '1' : $reason = 'Success -'; break;
			case '2' : $reason = 'Invalid API key - App must be granted a valid key by ShiftPlanning'; break;
			case '3' : $reason = 'Invalid token key - Please re-authenticate'; break;
			case '4' : $reason = 'Invalid Method - No Method with that name exists in our API'; break;
			case '5' : $reason = 'Invalid Module - No Module with that name exists in our API'; break;
			case '6' : $reason = 'Invalid Action - No Action with that name exists in our API'; break;
			case '7' : $reason = 'Authentication Failed - You do not have permissions to access the service'; break;
			case '8' : $reason = 'Missing parameters - Your request is missing a required parameter'; break;
			case '9' : $reason = 'Invalid parameters - Your request has an invalid parameter type'; break;
			case '10' : $reason = 'Extra parameters - Your request has an extra/unallowed parameter type'; break;
			case '12' : $reason = 'Create Failed - Your CREATE request failed'; break;
			case '13' : $reason = 'Update Failed - Your UPDATE request failed'; break;
			case '14' : $reason = 'Delete Failed - Your DELETE request failed'; break;
			case '20' : $reason = 'Incorrect Permissions - You don\'t have the proper permissions to access this'; break;
			case '90' : $reason = 'Suspended API key - Access for your account has been suspended, please contact ShiftPlanning'; break;
			case '91' : $reason = 'Throttle exceeded - You have exceeded the max allowed requests. Try again later.'; break;
			case '98' : $reason = 'Bad API Paramaters - Invalid POST request. See Manual.'; break;
			case '99' : $reason = 'Service Offline - This service is temporarily offline. Try again later.'; break;
			default : $reason = 'Error code not found'; break;
		}
		// return the reason text
		return $reason;
	}

	private function internal_errors( $errno )
	{// errors internal to the ShiftPlanning SDK
		switch( $errno )
		{// internal error messages
			case 1 :
				$message = 'The requested API method was not found in this SDK.';
				break;
			case 2 :
				$message = 'The ShiftPlanning API is not responding.';
				break;
			case 3 :
				$message = 'You must use the login method before accessing other modules of this API.';
				break;
			case 4 :
				$message = 'A session has not yet been established.';
				break;
			case 5 :
				$message = 'You must specify your Developer Key when using this SDK.';
				break;
			case 6 :
				$message = 'The ShiftPlanning SDK needs the CURL PHP extension.';
				break;
			case 7 :
				$message = 'The ShiftPlanning SDK needs the JSON PHP extension.';
				break;
			case 8 :
				$message = 'File doesn\'t exist.';
				break;
			case 9 :
				$message = 'Could not find the correct mime for the file supplied.';
				break;
			default :
				$message = 'Could not find the requested error message.';
				break;
		}
		return $message; exit;
	}

	private function api( )
	{// create the api call
		if( $this->_callback == null )
		{// method to call after successful api request
			$this->setCallback( 'getResponse' );
		}
		if( $this->getSession( ) )
		{// session already established, use token
			// remove the developer key from the request, since it's not necessary
			unset( $this->request['key'] );
			// set the token for this request, since the user is already authenticated
			$this->request['token'] = $_SESSION['token'];
		}
		else
		{// session has not been established, use developer key to access API
			try
			{//
				if( isset( $this->_key ) )
				{// developer key is set
					$this->request['key'] = $this->_key;
				}
				else
				{// developer key is not set
					throw new Exception( $this->internal_errors( 5 ) );
				}
			}
			catch( Exception $e )
			{//
				echo $e->getMessage( );
			}
		}
		// make the api request
		return $this->perform_request( );
	}

	private function getFileMimeType( $extension )
	{//
		$mimes = array(
			"ez" => "application/andrew-inset",
			"hqx" => "application/mac-binhex40",
			"cpt" => "application/mac-compactpro",
			"doc" => "application/msword",
			"bin" => "application/octet-stream",
			"dms" => "application/octet-stream",
			"lha" => "application/octet-stream",
			"lzh" => "application/octet-stream",
			"exe" => "application/octet-stream",
			"class" => "application/octet-stream",
			"so" => "application/octet-stream",
			"dll" => "application/octet-stream",
			"oda" => "application/oda",
			"pdf" => "application/pdf",
			"ai" => "application/postscript",
			"eps" => "application/postscript",
			"ps" => "application/postscript",
			"smi" => "application/smil",
			"smil" => "application/smil",
			"wbxml" => "application/vnd.wap.wbxml",
			"wmlc" => "application/vnd.wap.wmlc",
			"wmlsc" => "application/vnd.wap.wmlscriptc",
			"bcpio" => "application/x-bcpio",
			"vcd" => "application/x-cdlink",
			"pgn" => "application/x-chess-pgn",
			"cpio" => "application/x-cpio",
			"csh" => "application/x-csh",
			"dcr" => "application/x-director",
			"dir" => "application/x-director",
			"dxr" => "application/x-director",
			"dvi" => "application/x-dvi",
			"spl" => "application/x-futuresplash",
			"gtar" => "application/x-gtar",
			"hdf" => "application/x-hdf",
			"js" => "application/x-javascript",
			"skp" => "application/x-koan",
			"skd" => "application/x-koan",
			"skt" => "application/x-koan",
			"skm" => "application/x-koan",
			"latex" => "application/x-latex",
			"nc" => "application/x-netcdf",
			"cdf" => "application/x-netcdf",
			"sh" => "application/x-sh",
			"shar" => "application/x-shar",
			"swf" => "application/x-shockwave-flash",
			"sit" => "application/x-stuffit",
			"sv4cpio" => "application/x-sv4cpio",
			"sv4crc" => "application/x-sv4crc",
			"tar" => "application/x-tar",
			"tcl" => "application/x-tcl",
			"tex" => "application/x-tex",
			"texinfo" => "application/x-texinfo",
			"texi" => "application/x-texinfo",
			"t" => "application/x-troff",
			"tr" => "application/x-troff",
			"roff" => "application/x-troff",
			"man" => "application/x-troff-man",
			"me" => "application/x-troff-me",
			"ms" => "application/x-troff-ms",
			"ustar" => "application/x-ustar",
			"src" => "application/x-wais-source",
			"xhtml" => "application/xhtml+xml",
			"xht" => "application/xhtml+xml",
			"zip" => "application/zip",
			"au" => "audio/basic",
			"snd" => "audio/basic",
			"mid" => "audio/midi",
			"midi" => "audio/midi",
			"kar" => "audio/midi",
			"mpga" => "audio/mpeg",
			"mp2" => "audio/mpeg",
			"mp3" => "audio/mpeg",
			"aif" => "audio/x-aiff",
			"aiff" => "audio/x-aiff",
			"aifc" => "audio/x-aiff",
			"m3u" => "audio/x-mpegurl",
			"ram" => "audio/x-pn-realaudio",
			"rm" => "audio/x-pn-realaudio",
			"rpm" => "audio/x-pn-realaudio-plugin",
			"ra" => "audio/x-realaudio",
			"wav" => "audio/x-wav",
			"pdb" => "chemical/x-pdb",
			"xyz" => "chemical/x-xyz",
			"bmp" => "image/bmp",
			"gif" => "image/gif",
			"ief" => "image/ief",
			"jpeg" => "image/jpeg",
			"jpg" => "image/jpeg",
			"jpe" => "image/jpeg",
			"png" => "image/png",
			"tiff" => "image/tiff",
			"tif" => "image/tif",
			"djvu" => "image/vnd.djvu",
			"djv" => "image/vnd.djvu",
			"wbmp" => "image/vnd.wap.wbmp",
			"ras" => "image/x-cmu-raster",
			"pnm" => "image/x-portable-anymap",
			"pbm" => "image/x-portable-bitmap",
			"pgm" => "image/x-portable-graymap",
			"ppm" => "image/x-portable-pixmap",
			"rgb" => "image/x-rgb",
			"xbm" => "image/x-xbitmap",
			"xpm" => "image/x-xpixmap",
			"xwd" => "image/x-windowdump",
			"igs" => "model/iges",
			"iges" => "model/iges",
			"msh" => "model/mesh",
			"mesh" => "model/mesh",
			"silo" => "model/mesh",
			"wrl" => "model/vrml",
			"vrml" => "model/vrml",
			"css" => "text/css",
			"html" => "text/html",
			"htm" => "text/html",
			"asc" => "text/plain",
			"txt" => "text/plain",
			"rtx" => "text/richtext",
			"rtf" => "text/rtf",
			"sgml" => "text/sgml",
			"sgm" => "text/sgml",
			"tsv" => "text/tab-seperated-values",
			"wml" => "text/vnd.wap.wml",
			"wmls" => "text/vnd.wap.wmlscript",
			"etx" => "text/x-setext",
			"xml" => "text/xml",
			"xsl" => "text/xml",
			"mpeg" => "video/mpeg",
			"mpg" => "video/mpeg",
			"mpe" => "video/mpeg",
			"qt" => "video/quicktime",
			"mov" => "video/quicktime",
			"mxu" => "video/vnd.mpegurl",
			"avi" => "video/x-msvideo",
			"movie" => "video/x-sgi-movie",
			"ice" => "x-conference-xcooltalk"
		);
		try
		{//
			if( $mimes[ $extension ] )
			{// mime found
				return $mimes[ $extension ];
			}
			else
			{// mime not found
				throw new Exception( 'Mime for .' . $extension . ' not found' );
			}
		}
		catch( Exception $e )
		{//
			echo $e->getMessage( );
		}
	}

	private function getFileData( $file )
	{// get file details, (data, length, mimetype)
		try
		{//
			if( file_exists( $file ) )
			{// file
				$file_data['filedata'] = file_get_contents( $file );
				$file_data['filelength'] = strlen( $file_data['filedata'] );
				if( function_exists( 'mime_content_type' ) )
				{// mime_content_type function is available
					$file_data['mimetype'] = mime_content_type( $file );
				}
				else
				{//
					$parts = explode( '.', $file );
					$extension = strtolower( $parts[ sizeOf( $parts ) - 1 ] );
					$file_data['mimetype'] = $this->getFileMimeType( $extension  );
				}

				return array(
					'filedata' => $file_data['filedata'],
					'filelength' => $file_data['filelength'],
					'mimetype' => $file_data['mimetype']
				);
			}
			else
			{//
				throw new Exception( $this->internal_errors( 8 ) );
			}
		}
		catch( Exception $e )
		{//
			echo $e->getMessage( ); exit;
		}
	}

	private function perform_request( )
	{// perform the api request
		try
		{//
			$ch = curl_init( self::api_endpoint );

			$filedata = '';
			if( is_array( $this->requests ) )
			{//
				foreach( $this->requests as $key => $request )
				{//
					if( isset($request['filedata']) && $request['filedata'] )
					{//
						$filedata = $request['filedata'];
						unset( $this->requests[$key]['filedata'] );
					}
				}
			}

			$post = $filedata ? array( 'data'=> json_encode( $this->getRequest( ) ),
				'filedata' => $filedata ) : array( 'data' => json_encode( $this->getRequest( ) ) );

			curl_setopt( $ch, CURLOPT_URL, self::api_endpoint );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );

			// return the response from the api
			$response = curl_exec( $ch );
			$http_response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			curl_close( $ch );

			if( $http_response_code == 200 )
			{// response from API was a successful
				$temp = json_decode( $response, true );
				// decode the response and store each call response in its own array
				$this->setResponse( $temp );
				if( $this->_init == 1 )
				{// initialize a session
					$this->setSession( );
				}
				// raw response call
				$this->raw_response = $temp;
				if( $this->_debug == true )
				{// debug mode is on
					$request = json_encode( $this->getRequest( ) );
					$db = fopen('log.txt', 'a');
					$tmp_vals = array( );
					if( is_array( $this->response['data'][0] ) )
					{//
						foreach( $this->response['data'] as $n => $v )
						{//
							foreach( $v as $key => $val )
							{//
								$tmp_vals[$n][$key] = $val;
							}
						}
					}
					else
					{//
						foreach( $this->response['data'] as $n => $v )
						{//
							$tmp_vals[$n] = $v;
						}
					}
					fwrite( $db, date('m-d-Y h:i a'). "\n" . 'REQUEST: ' . $request . "\n"
						. 'RESPONSE STATUS: (' . $this->response['response'][0]['code']
						. ') ' . $this->response['response'][0]['text'] . " -- " . $this->response['response'][0]['error'] . "\n"
						. 'RESPONSE DATA: ' . json_encode( $tmp_vals ) . "\n\n" );
					fclose( $db );
				}
				// perform the callback method
				return $this->{ $this->_callback }( );
			}
			else
			{// response from API was unsuccessful
				throw new Exception( $this->internal_errors( 2 ) );
			}
		}
		catch( Exception $e )
		{//
			echo $e->getMessage();
		}
	}

	/*
	 * User Authentication Methods
	 *
	 */
	public function doLogin( $user = array( ) )
	{// perform a login api call
		return $this->setRequest(
			array(
				'module' => 'staff.login',
				'method' => 'GET',
				'username' => $user['username'],
				'password' => $user['password']
			)
		);
	}

	public function doLogout( )
	{// erase token and user data from current session
		$this->destroySession( );
	}

	/*
	 * Message Methods
	 *
	 */
	public function getMessages( )
	{// get messages for the currently logged in user
		return $this->setRequest(
			array(
				'module' => 'messaging.messages',
				'method' => 'GET'
			)
		);
	}

	public function getMessageDetails( $id )
	{// get message details for a specific message
		return $this->setRequest(
			array(
				'module' => 'messaging.message',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function createMessage( $message = array( ) )
	{// create a new message
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

	public function deleteMessage( $id )
	{// delete a message
		return $this->setRequest(
			array(
				'module' => 'messaging.message',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	public function getWallMessages( )
	{// get message wall
		return $this->setRequest(
			array(
				'module' => 'messaging.wall',
				'method' => 'GET'
			)
		);
	}

	public function createWallMessage( $message = array( ) )
	{// create a wall message
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

	public function deleteWallMessage( $id, $details = array( ) )
	{// delete a wall message
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
	public function getEmployees( )
	{// get a list of employees
		return $this->setRequest(
			array(
				'module' => 'staff.employees',
				'method' => 'GET'
			)
		);
	}

	public function getEmployeeDetails( $id )
	{// get details for a specific employee
		return $this->setRequest(
			array(
				'module' => 'staff.employee',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function updateEmployee( $id, $new_data = array( ) )
	{// update an employee record
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

	public function createEmployee( $data )
	{// create a new employee record
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

	public function deleteEmployee( $id )
	{// delete an employee
		return $this->setRequest(
			array(
				'module' => 'staff.employee',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	public function getStaffSkills( )
	{// get staff skills
		return $this->setRequest(
			array(
				'module' => 'staff.skills',
				'method' => 'GET'
			)
		);
	}

	public function getStaffSkillDetails( $id )
	{// get staff skill details
		return $this->setRequest(
			array(
				'module' => 'staff.skill',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function createStaffSkill( $skill_details = array( ) )
	{// create staff skill
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

	public function updateStaffSkill( $id, $skill_details = array( ) )
	{// update staff skill
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

	public function deleteStaffSkill( $id )
	{// delete staff skill
		return $this->setRequest(
			array(
				'module' => 'staff.skill',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	public function createPing( $ping_data = array( ) )
	{// create a ping
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

	/*
	 * Schedule Methods
	 *
	 */
	public function getSchedules( )
	{// get schedules
		return $this->setRequest(
			array(
				'module' => 'schedule.schedules',
				'method' => 'GET'
			)
		);
	}

	public function getScheduleDetails( $id )
	{// get schedule details
		return $this->setRequest(
			array(
				'module' => 'schedule.schedule',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function createSchedule( $schedule_details = array( ) )
	{// create a new schedule
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

	public function updateSchedule( $id, $schedule_details = array( ) )
	{// update an existing schedule
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

	public function deleteSchedule( $id )
	{// delete an existing schedule
		return $this->setRequest(
			array(
				'module' => 'schedule.schedule',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	public function getShifts( )
	{// get shifts
		return $this->setRequest(
			array(
				'module' => 'schedule.shifts',
				'method' => 'GET'
			)
		);
	}

	public function getShiftDetails( $id )
	{// get shift details
		return $this->setRequest(
			array(
				'module' => 'schedule.shift',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function updateShift( $id, $shift_details = array( ) )
	{// update shift details
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

	public function createShift( $shift_details = array( ) )
	{// create a new shift
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

	public function deleteShift( $id )
	{// delete a shift
		return $this->setRequest(
			array(
				'module' => 'schedule.shift',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	public function getVacationSchedules( $time_period = array( ) )
	{// get schedule vacations, pass start and end params to get vacations within a certian time-period
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

	public function getVacationScheduleDetails( $id )
	{// get vacation schedule details
		return $this->setRequest(
			array(
				'module' => 'schedule.vacation',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function createVacationSchedule( $vacation_details = array( ) )
	{// create a vacation schedule
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

	public function updateVacationSchedule( $id, $vacation_details = array( ) )
	{// update a vacation schedule
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

	public function deleteVacationSchedule( $id )
	{// delete a vacation schedule
		return $this->setRequest(
			array(
				'module' => 'schedule.vacation',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	public function getScheduleConflicts( $time_period = array( ) )
	{// get schedule conflicts
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
	public function getAdminSettings( )
	{// get admin settings
		return $this->setRequest(
			array(
				'module' => 'admin.settings',
				'method' => 'GET'
			)
		);
	}

	public function updateAdminSettings( $settings = array( ) )
	{// update admin settings
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

	public function getAdminFiles( )
	{// get administrator file listing
		return $this->setRequest(
			array(
				'module' => 'admin.files',
				'method' => 'GET'
			)
		);
	}

	public function getAdminFileDetails( $id )
	{// get admin file details
		return $this->setRequest(
			array(
				'module' => 'admin.file',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function updateAdminFile( $id, $details = array( ) )
	{// update admin file details
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

	public function createAdminFile( $file_details = array( ) )
	{// create new admin file
		$file_details = array_merge( $file_details, $this->getFileData( $file_details['filename'] ) );
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

	public function deleteAdminFile( $id )
	{// delete admin file
		return $this->setRequest(
			array(
				'module' => 'admin.file',
				'method' => 'DELETE',
				'id' => $id
			)
		);
	}

	public function getAdminBackups( )
	{// get admin backups
		return $this->setRequest(
			array(
				'module' => 'admin.backups',
				'method' => 'GET'
			)
		);
	}

	public function getAdminBackupDetails( $id )
	{// get admin backup details
		return $this->setRequest(
			array(
				'module' => 'admin.backup',
				'method' => 'GET',
				'id' => $id
			)
		);
	}

	public function createAdminBackup( $backup_details = array( )  )
	{// create an admin backup
		$backup_details = array_merge( $backup_details, $this->getFileData( $backup_details['filename'] ) );
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

	public function deleteAdminBackup( $id )
	{// delete an admin backup
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
	public function getAPIConfig( )
	{// get api config
		return $this->setRequest(
			array(
				'module' => 'api.config',
				'method' => 'GET'
			)
		);
	}

	public function getAPIMethods( )
	{// get all available api methods
		return $this->setRequest(
			array(
				'module' => 'api.methods',
				'method' => 'GET'
			)
		);
	}
}
?>

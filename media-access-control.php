<?php

	/**
	 * Media Restriction Access Controller
	 *
	 * @author     TERMINALFOUR
	 * @version    1.0
	 *
 	*/

	session_start();

	$hasAccess 		= FALSE;
	$contentType 	= '<t4 type="navigation" id="418"/>';//content which holds the list of urls of media to protect and the groups which have access to that media

	$arrayOfRestrictedMedia = explode(';', $contentType); //turn the list of restricted media paths into an array
	$requestedURL 			= $_SERVER["REQUEST_URI"];
	$pattern 				= '/evmsprivatemediarestricted\/([a-zA-Z0-9]{0,})\//'; //pattern finds a path such as 'evmsprivatemediarestricted/faculty/'
	$mediaCategoryRequested = preg_match($pattern, $requestedURL, $matches); //returns 1 if the path is found in the requested url e.g restricted/faculty
	$userGroup 				= strtolower($_SESSION["userGroup"]);

	if ( $mediaCategoryRequested == 1 && count($arrayOfRestrictedMedia) > 0  ) { // a restricted path exists in url

		$exactWordMatch = str_replace("/", "\/", $matches[0]); //escape the forward slashes so can use in preg_match
		$exactWordMatch = "/\b$exactWordMatch \b/";

		foreach ($arrayOfRestrictedMedia as $key => $value) { //find the array element which contains the restricted path requested and its groups

			// do an exact match so as to prevent for example 'student' matching 'student-reports'
			$pathRequested 	= preg_match($exactWordMatch,$value);

			if ( $pathRequested == 1 ) {

				$keyContainingPath = $key; // return the element key
				break;

			}

		}

		if ( isset($keyContainingPath) ) {

			//get the groups associated with the path which are allowed access
			$groupsAllowedAccess = strtolower(substr($arrayOfRestrictedMedia[$keyContainingPath], strpos($arrayOfRestrictedMedia[$keyContainingPath], " ") + 1));

			//find the users group in the list of allowed groups
			$userHasAccess = strpos($groupsAllowedAccess, $userGroup);

			if ( $userHasAccess !== FALSE ) { // the user is a member of an allowed group give them access

				$hasAccess = TRUE;

			}

		}

	}

	if ( $hasAccess == FALSE ) {

		echo 'Access Denied.';

	} else {

		// function to get the mime-type of the file
		function getMimeType($fileName){
			
			$extension = pathinfo($fileName, PATHINFO_EXTENSION);
			$extension = strtolower($extension);

		    $mimeTypesArray = array(
		        "pdf" 	=> "application/pdf",
		        "txt" 	=> "text/plain",
		        "html" 	=> "text/html",
		        "htm" 	=> "text/html",
		        "exe" 	=> "application/octet-stream",
		        "zip" 	=> "application/zip",
		        "doc" 	=> "application/msword",
		        "xls" 	=> "application/vnd.ms-excel",
		        "ppt" 	=> "application/vnd.ms-powerpoint",
		        "gif" 	=> "image/gif",
		        "png" 	=> "image/png",
		        "jpeg"	=> "image/jpg",
		        "jpg" 	=>  "image/jpg",
		        "php" 	=> "text/plain",
		        "csv" 	=> "text/csv",
		        "xlsx" 	=> "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
		        "pptx" 	=> "application/vnd.openxmlformats-officedocument.presentationml.presentation",
		        "docx" 	=> "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
		    );

		    if ( isset($mimeTypesArray[$extension]) ) {
		        
		        return $mimeTypesArray[$extension];

		    } else {
		        
		        return 'application/octet-stream';

		    }

		}

		// sanitize the file request, keep just the name and extension, also replaces the file location with a preset one e.g. '/myfiles/'
		$path 		= $_SERVER['REQUEST_URI'];
		$fileName 	= substr($path, strpos($path,'evmsprivatemediarestricted')+27);
		$filePath  	= '/Apache2.2/htdocs/media/evmsprivatemediarestricted/'.$fileName;

		$filePath 	= urldecode($filePath);

		// check file is readable or not exists
		if ( !is_readable($filePath) ){
		
			die('File is not readable or does not exist!');

		}
	 
		// get mime type of file by extension
		$mimeType = getMimeType($fileName);

		// set headers
		header('Pragma: public');
		header('Expires: -1');
		header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header("Content-Disposition: attachment; filename=\"$fileName\"");
		header("Content-Length: " . filesize($filePath));
		header("Content-Type: $mimeType");
		header("Content-Description: File Transfer");

		// read the file as chunks to reduce memory usage
		if ( $file = fopen( $filePath,'rb') ) {
		    
		    ob_end_clean();
		 
		    while( !feof($file) and (connection_status()==0) ) {
		        
		        print(fread($file, 8192));
		        flush();

		    }
		 
		    @fclose($file);
		    exit;

		}

	}

?>
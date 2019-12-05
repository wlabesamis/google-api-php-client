<?php
/*
 * Copyright 2011 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
$options = getopt("p:f:");

if ( empty($options['f']) ) {
    echo "\nError: Missing option -p or -f, see example command below\n";
    echo "> php uploadFile.php -p parentid -f filename\n\n";
    exit;
}

$parentId = null;

if ( !empty($options['p']) ) {
   $parentId = $options['p'];
}

$filename = $options['f'];
$mimeType = 'text/plain';
$credentialsFile = __DIR__ . '/../credentials.json';

include_once __DIR__ . '/../vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient($credentialsFile)
{
    $client = new Google_Client();
    $client->setApplicationName('People API PHP Quickstart');
    $client->setAuthConfig($credentialsFile);
    $client->setAccessType('offline');
    $client->addScope(Google_Service_Drive::DRIVE_FILE);
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.
$client = getClient($credentialsFile);
$service = new Google_Service_Drive($client);


//Insert a file
$file = new Google_Service_Drive_DriveFile();
$file->setName($filename);
$file->setDescription('SQL backup file');
$file->setMimeType($mimeType);
// Set the parent folder.
if ($parentId != null) {

//    $file->setId($parentId);
    $file->setParents(array($parentId));
}

$data = file_get_contents($filename);

$createdFile = $service->files->create($file, array(
  'data' => $data,
  'mimeType' => $mimeType,
  'uploadType' => 'multipart'
));


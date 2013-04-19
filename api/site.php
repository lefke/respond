<?php 

/**
 * A protected API call to retrieve the current site
 * @uri /site/create
 */
class SiteCreateResource extends Tonic\Resource {

    /**
     * @method POST
     */
    function post() {
        
        parse_str($this->request->data, $request); // parse request

        $friendlyId = $request['friendlyId'];
        $name = $request['name'];
        $email = $request['email'];
        $password = $request['password'];
        $s_passcode = $request['passcode'];
        
        // defaults
        $firstName = 'New';
        $lastName = 'User';
        $domain = APP_URL.'/sites/'.$friendlyId;
    	$domain = str_replace('http://', '', $domain);
		$logoUrl = 'sample-logo.png';
		$template = 'simple';
        
        if($s_passcode == PASSCODE){
            
            $isUserUnique = User::IsLoginUnique($email);
            
            if($isUserUnique==false){
                return new Tonic\Response(Tonic\Response::CONFLICT);
            }
            
            // add the site
    	    $site = Site::Add($domain, $name, $friendlyId, $logoUrl, $template, $email); // add the site
            
            // add the admin
            $user = User::Add($email, $password, $firstName, $lastName, 'Admin', $site->SiteId);
            
            // create the home page
        	$description = '';
    		$content = '';
    		$filename = '../layouts/home.html';
    				
    		if(file_exists($filename)){
    			$content = file_get_contents($filename);
    		}
    		
            $homePage = Page::Add('index', 'Home', $description, -1, $site->SiteId, $user->UserId);
            $homePage->Activate();
            
    		Publish::PublishFragment($site->FriendlyId, $homePage->PageUniqId, 'publish', $content);
    		
    		// add the general page type and create a list
    		$pageType = PageType::Add('page', 'Page', 'Pages', $site->SiteId, $user->UserId, $user->UserId);
    		
    		// create the sample page
    		$content = '';
    		$filename = '../layouts/about.html';
    				
    		if(file_exists($filename)){
    			$content = file_get_contents($filename);
    		}
            
    		$aboutUs = Page::Add('about', 'About', $description, $pageType->PageTypeId, $site->SiteId, $user->UserId);
            $aboutUs->Activate();
    		
    		Publish::PublishFragment($site->FriendlyId, $aboutUs->PageUniqId, 'publish', $content);
    			
    		// create the contact us page
    		$content = '';
    		$filename = '../layouts/contact.html';
    				
    		if(file_exists($filename)){
    			$content = file_get_contents($filename);
    		}
    		
            $contactUs = Page::Add('contact', 'Contact', $description, $pageType->PageTypeId, $site->SiteId, $user->UserId);
            $contactUs->Activate();
        
    		Publish::PublishFragment($site->FriendlyId, $contactUs->PageUniqId, 'publish', $content);
    			
    		// create the error page
    		$content = '';
    		$filename = '../layouts/error.html';
    				
    		if(file_exists($filename)){
    			$content = file_get_contents($filename);
    		}
    		
            $pageNotFound = Page::Add('error', 'Page Not Found', $description, $pageType->PageTypeId, $site->SiteId, $user->UserId);
            $pageNotFound->Activate();
        
    		Publish::PublishFragment($site->FriendlyId, $pageNotFound->PageUniqId, 'publish', $content);
    		
    		// create the menu
    		$homeUrl = '';
    		$aboutUsUrl = 'page/about';
    		$contactUsUrl = 'page/contact';
    		MenuItem::Add('Home', '', 'primary', $homeUrl, $homePage->PageId, 0, $site->SiteId, $user->UserId, $user->UserId);
            MenuItem::Add('About', '', 'primary', $aboutUsUrl, $aboutUs->PageId, 2, $site->SiteId, $user->UserId, $user->UserId);
    		MenuItem::Add('Contact', '', 'primary', $contactUsUrl, $contactUs->PageId, 3, $site->SiteId, $user->UserId, $user->UserId);
    		
    		// publishes a template for a site
    		Publish::PublishTemplate($site, $template);
    		
    		// publish the site
    		Publish::PublishCommonForEnrollment($site->SiteUniqId);
    		Publish::PublishSite($site->SiteUniqId);
            
            // send email
            $subject = 'RespondCMS: New site created';
    
    		$message = '<html><head><title>'.$subject.'</title></head>';
    		$message = $message.'<body><table><col width="200">';
    		$message = $message.'<tr><td>Email:</td><td>'.$email.'</td></tr>';
    		$message = $message.'<tr><td>Company Name:</td><td>'.$name.'</td></tr>';
    		$message = $message.'<tr><td>Site Url:</td><td>'.$domain.'</td></tr>';
    		
    		$message = $message.'</table></body></html>';
    
    		$headers  = 'MIME-Version: 1.0' . "\r\n";
    		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    		$headers .= 'From: no-reply@respondcms.com' . "\r\n" .
        				'Reply-To: no-reply@respondcms.com' . "\r\n";
    
    		mail('admin@respondcms.com', $subject, $message, $headers);
    		
            return new Tonic\Response(Tonic\Response::OK);
        }
        else{
            return new Tonic\Response(Tonic\Response::UNAUTHORIZED);
        }

        
    }
}

/**
 * A protected API call to retrieve the current site
 * @uri /site/current
 */
class SiteCurrentResource extends Tonic\Resource {

    /**
     * @method GET
     */
    function get() {
        // get an authuser
        $authUser = new AuthUser();

        if(isset($authUser->UserUniqId)){ // check if authorized

            $site = Site::GetBySiteUniqId($authUser->SiteUniqId);

            $arr = $site->ToAssocArray();

            // return a json response
            $response = new Tonic\Response(Tonic\Response::OK);
            $response->contentType = 'applicaton/json';
            $response->body = json_encode($arr);

            return $response;
        }
        else{
            return new Tonic\Response(Tonic\Response::UNAUTHORIZED);
        }
    }
}

/**
 * A protected API call to publish the site
 * @uri /site/publish
 */
class SitePublishResource extends Tonic\Resource {

    /**
     * @method GET
     */
    function get() {
        // get an authuser
        $authUser = new AuthUser();

        if(isset($authUser->UserUniqId)){ // check if authorized

            $site = Site::GetBySiteUniqId($authUser->SiteUniqId);

            Publish::PublishSite($site->SiteUniqId);

            $response = new Tonic\Response(Tonic\Response::OK);
       
            return $response;
        }
        else{
            return new Tonic\Response(Tonic\Response::UNAUTHORIZED);
        }
    }
}


/**
 * A protected API call to view, edit, and delete a site
 * @uri /site/verification/generate
 */
class SiteVerificationGenerateResource extends Tonic\Resource {

    /**
     * @method POST
     */
    function generate() {

        // get an authuser
        $authUser = new AuthUser();

        if(isset($authUser->UserUniqId)){ // check if authorized

            parse_str($this->request->data, $request); // parse request

            $name = $request['name'];
            $content = $request['content'];
		
		    $site = Site::GetBySiteId($authUser->SiteId);
		
		    $dir = '../sites/'.$site->FriendlyId.'/';
		
		    Utilities::SaveContent($dir, $name, $content);
            
            return new Tonic\Response(Tonic\Response::OK);
        
        } else{ // unauthorized access

            return new Tonic\Response(Tonic\Response::UNAUTHORIZED);
        }

        return new Tonic\Response(Tonic\Response::NOTIMPLEMENTED);
    }

}

/**
 * A protected API call to view, edit, and delete a site
 * @uri /site/{siteUniqId}
 */
class SiteResource extends Tonic\Resource {

    /**
     * @method GET
     */
    function get($siteUniqId) {
        // get an authuser
        $authUser = new AuthUser();

        if(isset($authUser->UserUniqId)){ // check if authorized

            $site = Site::GetBySiteUniqId($siteUniqId);

            $arr = $site->ToAssocArray();

            // return a json response
            $response = new Tonic\Response(Tonic\Response::OK);
            $response->contentType = 'applicaton/json';
            $response->body = json_encode($arr);

            return $response;
        }
        else{
            return new Tonic\Response(Tonic\Response::UNAUTHORIZED);
        }
    }

    /**
     * @method POST
     */
    function update($siteUniqId) {

        // get an authuser
        $authUser = new AuthUser();

        if(isset($authUser->UserUniqId)){ // check if authorized

            parse_str($this->request->data, $request); // parse request

            $domain = $request['domain'];
            $name = $request['name'];
            $analyticsId = $request['analyticsId'];
            $facebookAppId = $request['facebookAppId'];
            $primaryEmail = $request['primaryEmail'];
            $timeZone = $request['timeZone'];

            Site::Edit($siteUniqId, $domain, $name, $analyticsId, $facebookAppId, $primaryEmail, $timeZone);

            return new Tonic\Response(Tonic\Response::OK);
        
        } else{ // unauthorized access

            return new Tonic\Response(Tonic\Response::UNAUTHORIZED);
        }

        return new Tonic\Response(Tonic\Response::NOTIMPLEMENTED);
    }

}

/**
 * A protected API call to view, edit, and delete a site
 * @uri /site/logo/{siteUniqId}
 */
class SiteLogoResource extends Tonic\Resource {

    /**
     * @method POST
     */
    function update($siteUniqId) {

        // get an authuser
        $authUser = new AuthUser();

        if(isset($authUser->UserUniqId)){ // check if authorized

            parse_str($this->request->data, $request); // parse request

            $logoUrl = $request['logoUrl'];

            Site::EditLogo($siteUniqId, $logoUrl);

            return new Tonic\Response(Tonic\Response::OK);
        
        } else{ // unauthorized access

            return new Tonic\Response(Tonic\Response::UNAUTHORIZED);
        }

        return new Tonic\Response(Tonic\Response::NOTIMPLEMENTED);
    }

}



?>
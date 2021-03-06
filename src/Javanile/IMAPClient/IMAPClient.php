<?php
/**
 * 
 * 
 * 
 */

namespace Javanile\IMAPClient;

class IMAPClient 
{
	/**
     *
     * @var type 
     */
	private $host = null;
    
    /**
     *
     * @var type 
     */
	private $port = null;
	
	/**
     *
     * @var type 
     */
	private $username = null;
	
	/**
     *
     * @var type 
     */
	private $password = null;
    
    /**
     *
     * @var type 
     */
	private $provider = null;
	
    /**
     * 
     * 
     */
    private $mailbox = null;
    
	/**
     *
     * @var type 
     */
	private $stream = null;
    
    /**
     *
     * @var type 
     */
	private $path = null;
    
	/**
     * 
     * @param type $args
     */
	public function __construct($args)
    {
		//
		error_reporting(E_ALL);
		ini_set("display_errors",1);
		
        //
        $this->username = strtolower(trim($args['username']));
		$this->password = $args['password'];
        $this->provider = Functions::getEmailProvider($this->username);
        
        //
        if (!isset($args['host']) || !$args['host'])
        {
            switch ($this->provider)
            {
                case '@gmail.com': $args['host'] = 'imap.gmail.com'; break;
            }
        } 
       
        //
        $this->host = $args['host'];
        
        //
        if (!isset($args['port']) || !$args['port'])
        {
            switch ($this->provider)
            {
                case '@gmail.com': $args['port'] = 993; break;
            }
        }
        
        //
 		$this->port = $args['port'];
		
        //
        if (!isset($args['mailbox']) || !$args['mailbox']) 
        {
            switch ($this->provider)
            {
                case '@gmail.com':
                    $args['mailbox'] = 
                        '{'.$this->host.
                        ':'.$this->port.
                        '/imap/ssl/novalidate-cert}'; 
                    break;      
            }
        }
        
        //
        $this->mailbox = $args['mailbox'];
        
        //
        if (!isset($args['path']) || !$args['path'])
        {
            $args['path'] = sys_get_temp_dir().'/Javanile/IMAPClient';
        }
        
        //
		$this->path = rtrim($args['path'],'/').'/'.$this->username.'/'.time();		
	}
	
	/**
     * 
     * @return type
     */
	public function login() 
    {
        //
		$this->stream = @imap_open(
			$this->mailbox,
			$this->username,
			$this->password
		);
        
        //
        return $this->success();
	}
    
    /**
     * 
     * @return \Javanile\IMAPClient\IMAPClientEmail
     */
	public function search($query)
    {	
		//
		$array = array();
		
		//
		$emails = imap_search($this->stream, $query);		
       
        //
		if ($emails) 
        {			
            //
			foreach ($emails as $number) 
            { 
				$array[] = new IMAPClientEmail($this, $this->stream, $number);
			}			
		}

		//
		return $array;		
	}
	
    /**
     * 
     * @return \Javanile\IMAPClient\IMAPClientEmail
     */
	public function getEmails()
    {	
		//
		$array = array();
		
		//
		$emails = imap_search($this->stream, 'ALL');		
       
		//
		if ($emails) 
        {			
            //
			foreach($emails as $number) 
            { 
				$array[] = new IMAPClientEmail($this, $this->stream, $number);
			}			
		}

		//
		return $array;		
	}
	

    /**
     * 
     * @param type $since
     * @return \Javanile\IMAPClient\gmail_fetch_client_mail
     */
	public function getEmailsSince($since)
    {
		//
		$array = array();
		
		//
		$emails = imap_search($this->stream, 'SINCE '.$since);

		//
		if ($emails) 
        {
            //
			foreach($emails as $number) 
            {
                //
				$array[] = new IMAPClientEmail($this, $this->stream, $number);
			}
		}

		//
		return $array;
	}


    ##
	public function getAllEmails()
    {
		##
		$out = array();

		##
		$emails = imap_search($this->stream, 'ALL');
       
		##
		if ($emails) {
			rsort($emails);
			foreach($emails as $number) {
				$out[] = new gmail_fetch_client_mail($this->stream,$number,$this->path, $this);
			}
		}

		##
		return $out;
	}

    ##
	public function getSendedEmails() {

		##
		$out = array();

		##
		$emails = imap_search($this->stream,'ALL');
       
		##
		if ($emails) {
			rsort($emails);
			foreach($emails as $number) {
				$out[] = new gmail_fetch_client_mail($this->stream,$number,$this->path, $this);
			}
		}

		##
		return $out;
	}
    
    
    /**
     * 
     * @param type $date
     * @return \gmail_fetch_client_mail
     */
	public function getEmailsOnDate($date)
    {
		//
		$array = array();

		//
		$emails = imap_search($this->stream, "ON {$date}");
       
		//
		if ($emails)
        {
			foreach($emails as $number)
            {
				$array[] = new IMAPClientEmail($this, $this->stream, $number);
			}
		}

		//
		return $array;
	}
    
    ##
    public function getEmailByMessageId($id, $date)
    {
        
        $date = str_replace(gmail_fetch_client::$labels_fix,gmail_fetch_client::$labels_to, strtolower($date));
        
        /**/
        $since = date('Y-m-d',strtotime('-1 days',strtotime($date)));
        
        $before = date('Y-m-d',strtotime('+1 days',strtotime($date)));
        
        $search = "SINCE {$since} BEFORE {$before}";
        /** /
        $on = date('Y-m-d', strtotime($date));
        
        $search = "ON {$on}";
        /**/
       
        
        ##
		$out = array();

		##
		$emails = imap_search($this->stream, $search);
       
		##
		if ($emails) {
			rsort($emails);
			foreach($emails as $number) {
                //echo $number."<br/>";
                $email = new gmail_fetch_client_mail($this->stream,$number,$this->path,$this);
				
                if ($email->testMessageId($id))
                {
                    
                   return $email;
                    
                }

                
			}
		}

        
   
    }

    /**
     * 
     */
    public function getFolders() 
    {
        //
        $folders = @imap_list($this->stream, $this->mailbox, '*');
        
        //
        return $folders;
    }

    /**
     * 
     * @param type $folder
     * @return type
     */
    public function setFolder($folder) 
    {
        //
        if ($folder[0] == '{')
        {
            $folder = substr($folder, strpos($folder, '}') + 1);
        }
            
        //
        imap_reopen(
            $this->stream, 
            $this->mailbox . $folder
        );
        
        //
        return $this->success();
    }

    ##
	public function createFolder($folder) 
    {
        ##
        imap_createmailbox($this->stream,"{$this->host}$folder");
        
        ##
		return !imap_errors();
	}
    
	/**
     * 
     * 
     */
	public function close()
    {		
        //
        imap_expunge($this->stream); 
		
        //
        imap_close($this->stream, CL_EXPUNGE);
	}
    
    /**
     * 
     * 
     */
    private function success()
    {
        //
        $this->errors = imap_errors();
        
        //
        return !$this->errors || !count( $this->errors );
    }
    
    /**
     * 
     * 
     */
    public function errors()
    {
        //
        return $this->errors;
    }
    
    /**
     * 
     * 
     */
    public function printableErrors()
    {
        //
        $message = "";
       
        //
        foreach ($this->errors as $error)    
        {
            $message .= ' - ERROR: '.$error.'<br/>';
        }
        
        //
        return $message;
    }
}


<?php
/**
 * @version     1.0.0
 * @package     com_imixtix
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Nanda Kishore M <nandaatwork@yahoo.com> - http://www.jomexperts.com
 */

// No direct access
defined('_JEXEC') or die ;

jimport('joomla.application.component.modellist');

class ImixtixModelEvents extends JModelList {

	/**
	 * Constructor.
	 *
	 * @param	array    An optional associative array of configuration settings.
	 * @see		JController
	 * @since   1.6
	 */
	public function __construct($config = array()) {

		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'a.id',
				'event_title', 'a.event_title',
				'event_category', 'a.event_category',
				'start_date', 'a.start_date',
				'status', 'a.status'
			);			
		}
		parent::__construct($config);
		
		$mainframe = JFactory::getApplication(); 
        // Get pagination request variables
        $limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = JRequest::getVar('limitstart', 0, '', 'int'); 
        // In case limit has been changed, adjust it
        $limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0); 
        $this->setState('limit', $limit);
        $this->setState('limitstart', $limitstart);		
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'Events', $prefix = 'ImixtixTable', $config = array()) {

		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null) {

		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app -> getUserStateFromRequest($this -> context . '.filter.search', 'filter_search');
		$this -> setState('filter.search', $search);
		$published = $app -> getUserStateFromRequest($this -> context . '.filter.state', 'filter_published', '', 'string');
		$this -> setState('filter.state', $published);
		$category = $app -> getUserStateFromRequest($this -> context . '.filter.category', 'filter_category');
		$this -> setState('filter.category', $category);
		// Load the parameters.
		$params = JComponentHelper::getParams('com_imixtix');
		$this -> setState('params', $params);
		// List state information.
		parent::populateState('a.id', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId($id = '') {

		// Compile the store id.
		$id .= ':' . $this -> getState('filter.search');
		$id .= ':' . $this -> getState('filter.state');
		$id .= ':' . $this -> getState('filter.category');
		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery() {
	// Create a new query object.
		//$db = $this -> getDbo();
                $db=JFactory::getDBO();
                
		$query = $db -> getQuery(true);

		// Select the required fields from the table.
		$query -> select($this -> getState('list.select', 'a.*'));
		$query -> select('a.id as eventid');		
		$query -> from('`#__imixtix_events` AS a');	

		// Join over the categories
		$query -> select('b.cat_name');
		$query -> join('LEFT', '#__imixtix_categories AS b ON b.id = a.event_category');
		
		// Join over the tickets
		//$query -> select('COUNT(c.event_id) AS sold');
//                $query -> select('Count(c.sold_quantity) AS sold');
		//$query -> join('LEFT', '#__imixtix_tickets AS c ON c.event_id = a.id');
		
                
                //// Join over the tickets
		$query -> select('COUNT(ticket.event_id) AS no_of_tic');
                $query -> select('SUM(ticket.sold_quantity) AS sold');
                $query -> select('SUM(ticket.quantity) AS totalQuantity');
		

		

                $query -> join('LEFT', '#__imixtix_tickets AS ticket ON ticket.event_id = a.id');










		//$query -> where('sale.is_tic_sent=1  and  payment_status=1');
                
                
		// Filter by published state.
		$published = $this -> getState('filter.state');
		if (is_numeric($published)) {
			$query -> where('a.status = ' . (int)$published);
		} elseif ($published === '') {
			$query -> where('(a.status IN (0, 1))');
		}

                 
                     
                
		// Filter by categories.
		$category = $this -> getState('filter.category');
		if (is_numeric($category)) {
			$query -> where('a.event_category = ' . (int)$category);
		} elseif ($category === '') {
			$query -> where('(a.event_category IS NOT NULL)');
		}

		// Filter by search in title
		$search = $this -> getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query -> where('a.id = ' . (int) substr($search, 3));
			} else {
				$search = $db -> Quote('%' . $db -> getEscaped($search, true) . '%');
				$query -> where('(' . 'a.event_title LIKE ' . $search . ' OR a.event_desc LIKE ' . $search . ')');
			}
		}
		$query -> group('a.id');
		
		// Add the list ordering clause.
		$orderCol = $this -> state -> get('list.ordering');
		$orderDirn = $this -> state -> get('list.direction');
		if ($orderCol && $orderDirn) {
			$query -> order($db -> getEscaped($orderCol . ' ' . $orderDirn));
		}
		//echo nl2br(str_replace('#__','#__',$query));
		 
                return $query;
	}

	/**
	 * Method to get Sales Total
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getSalesTotal($eid) {

		

		$query = $this -> _db -> getQuery(true);
                $query= "select sum((t.total_price)) from #__imixtix_tickets as t inner join #__imixtix_salestickets as s on s.ticid=t.id WHERE s.status='COMPLETED' and  s.payment_status=1 and s.eventid='" . $eid."'";
		$this -> _db -> setQuery($query);
		return $this -> _db -> loadResult();	

/*

		$query = $this -> _db -> getQuery(true);
		// Select the required fields from the table.
		$query -> select('SUM(b.mc_gross)'); // * b.quantity
		$query -> from('`#__imixtix_salestickets` AS a');
		// Join over the orders
		$query -> join('LEFT', '#__imixtix_orders AS b ON b.trackingid = a.trackingid');
		$query -> where("a.eventid = '" . $eid."' and b.payment_status='COMPLETED'");
                $this -> _db -> setQuery($query);
		
		return $this -> _db -> loadResult();	
*/
	}

	/**
	 * Method to get a single record.
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem() {

		$data = JRequest::get('request');
		if (!empty($data['cid'][0])) {
			$this -> _db -> setQuery("SELECT * FROM `#__imixtix_events` WHERE id = " . $data['cid'][0]);
			return $this -> _db -> loadObject();
		}
		return false;
	}
	

	/**
	 * Method to publish / unpublish a record(s).
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	public function publish($data) {

		if (is_array($data['cid'])) {
			$where = 'id IN (' . implode(',', $data['cid']) . ')';
		}
		if ($data['task'] == 'publish') {
			$status = 1;
		} else {
			$status = 0;
		}
		// Update the publishing state for rows with the given primary keys.
		$this -> _db -> setQuery('UPDATE `#__imixtix_events` SET `status` = ' . $status . ' WHERE ' . $where . '');
		$this -> _db -> query();
		// Check for a database error.
		if ($this -> _db -> getErrorNum()) {
			$this -> setError($this -> _db -> getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Method to store a event.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	public function store($data) {

ini_set("post_max_size", "300M");
ini_set("upload_max_filesize", "300M");
jimport('joomla.filesystem.file');
		$file_type = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
		

	 	$path = JPATH_SITE . DS . "images" . DS . "imixtix_events" . DS;
		if(!($path))
		{
			$oldmask = umask(0);
			mkdir($path, 0777, true);
			umask($oldmask);
		}		


		$file1 = JRequest::getVar('image1', null, 'files', 'array');
		if (!empty($file1) && isset($file1) && $file1['name'] != '') {
			$filename = JFile::makeSafe($file1['name']);
			$name = JFile::stripExt($filename);
			$ext = JFile::getExt($filename);
			$fileName = $name . '_' . date('mdYHis') . '.' . $ext;
			$src = $file1['tmp_name'];
			$dest = $path . $fileName;
                             
                        if (in_array($ext, $file_type)) {
                                JFile::upload($src, $dest);
				if ($path . $data['old_image1']) unlink($path . $data['old_image1']);
			} else {
				//Redirect and notify user file is not right extension
				$msg = JText::_('FILE_TYPE_INVALID');
			}
			if (!empty($msg)) {
				return $msg;
			}
			$data['image1'] = $fileName;
		}
		$file2 = JRequest::getVar('image2', null, 'files', 'array');
		if (!empty($file2) && isset($file2) && $file2['name'] != '') {
			$filenam2 = JFile::makeSafe($file2['name']);
			$name = JFile::stripExt($filenam2);
			$ext = JFile::getExt($filenam2);
			$fileName2 = $name . '_' . date('mdYHis') . '.' . $ext;
			$src = $file2['tmp_name'];
			$dest = $path . $fileName2;
			
			if (in_array($ext, $file_type)) {
				JFile::upload($src, $dest);
				if ($path . $data['old_image2']) unlink($path . $data['old_image2']);
			} else {
				//Redirect and notify user file is not right extension
				$msg = JText::_('FILE_TYPE_INVALID');
			}
			if (!empty($msg)) {
				return $msg;
			}
			$data['image2'] = $fileName2;
		}
                
          
                $valid_extnsion = array('mp3', 'mp4');
                if(isset($_FILES['mp3']) && !empty($_FILES['mp3']))
                {
                    $ext = pathinfo($_FILES['mp3']['name'], PATHINFO_EXTENSION);
                    $tmp_name= $_FILES['mp3']['tmp_name'];
                 echo    $name=$_FILES['mp3']['name'];
                    if (in_array($ext, $valid_extnsion)) 
			{
             
             echo '====='.    move_uploaded_file($tmp_name, $path.$name);
                             if (file_exists($data['old_mp3_audio']))  { unlink($path . $data['old_mp3_audio']); }
                        }
                        else
                        {
                            $msg = JText::_('FILE_TYPE_INVALID');
                        }
                }

       die;         
            /*    
                
                $file_type_mp = array('mp3', 'mp4');
                $mp3_file=JRequest::getVar('mp3', null, 'files', 'array');
                if (!empty($mp3_file) && isset($mp3_file) && $mp3_file['name'] != '') {
		echo '<br/>'.	$filenam2_mp = JFile::makeSafe($mp3_file['name']);
		echo '<br/>'.        $name = JFile::stripExt($filenam2_mp);
		echo '<br/>'. 	$ext = JFile::getExt($filenam2_mp);
		//$fileName2_mp = time().'.' . $ext;
		echo '<br/>'.	$fileName2_mp =$filenam2_mp;
		//echo '<br/>'.	$src = $mp3_file['tmp_name'];
                echo '<br/>'.	$src = $_FILES['mp3']['tmp_name'];
                
                
		echo '<br/>'.	$dest = $path.$fileName2_mp;
                 	if (in_array($ext, $file_type_mp)) 
			{
                echo '<br/>'.                JFile::upload($src, $dest);
                                if (file_exists($data['old_mp3_audio']))  { unlink($path . $data['old_mp3_audio']); }
                        } else {
				//Redirect and notify user file is not right extension
				$msg = JText::_('FILE_TYPE_INVALID');
			}
  

                        if (!empty($msg)) {
				return $msg;
			}
			$data['mp3_audio'] = $fileName2_mp;
                        
                }
                
*/
                
		if (!isset($data ['status'])) {
			$data['status'] = 0;
		}
		if (!empty($data['id'])) {
			$data['modified'] = date('Y-m-d H:i:s');
		} else {
			$data['created'] = date('Y-m-d H:i:s');
		}
		if (isset ($data['use_paypal']) && $data ['use_paypal'] == 0) {
			$data['paypal_email'] = '';
		}
		//echo "<PRE>"; print_r ($data); exit;

		$table = $this -> getTable();
		// Bind the data.
		if (!$table -> bind($data)) {
			$this -> setError($table -> getError());
			return false;
		}
		// Check the data.
		if (!$table -> check()) {
			$this -> setError($table -> getError());
			return false;
		}
		// Store the data.
		if (!$table -> store()) {
			$this -> setError($table -> getError());
			return false;
		}
		$event_id = $table -> id;
		if (!empty($data['ticketIDs']) && $event_id) {
			$sql = 'UPDATE #__imixtix_tickets SET event_id = ' . $event_id . ' WHERE id IN (' . $data['ticketIDs'] . ')';
			$this -> _db -> setQuery($sql);
			$this -> _db -> query();
		}
		return $event_id;
	}

	/**
	 * Method to delete a record(s).
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	public function remove($data) {

		if (is_array($data['cid'])) {
			$where = 'id IN (' . implode(',', $data['cid']) . ')';
		}
		// Update the publishing state for rows with the given primary keys.
		$this -> _db -> setQuery('DELETE FROM `#__imixtix_events` WHERE ' . $where . '');
		$this -> _db -> query();
		// Check for a database error.
		if ($this -> _db -> getErrorNum()) {
			$this -> setError($this -> _db -> getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Method to retrive record(s) of catetory table.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	function getCategories() {

		$qry_categories = "SELECT id, cat_name FROM #__imixtix_categories WHERE status = 1 ORDER BY cat_name";
		$this -> _db -> setQuery($qry_categories);
		if (!$this -> _db -> query()) {
			JError::raiseError(500, $this -> _db -> getErrorMsg());
		}
		return $this -> _db -> loadObjectList();
	}

	/**
	 * Method to retrive record(s) of venue table.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	function getVenues() {

		$sql = "SELECT * FROM #__imixtix_venues WHERE status = 1 ORDER BY venue_name";
		$this -> _db -> setQuery($sql);
		if (!$this -> _db -> query()) {
			JError::raiseError(500, $this -> _db -> getErrorMsg());
		}
		return $this -> _db -> loadObjectList();
	}

	/**
	 * Method to retrive record(s) of Tickets table.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	function getTickets() {

		$cid = JRequest::getVar('cid');
		if ($cid) {
			$str = " WHERE event_id = " . $cid[0];
		} else {
			$tids = JRequest::getVar('tids');
			$str = " WHERE id IN (" . $tids . ")";
		}
		$sql = "SELECT * FROM #__imixtix_tickets " . $str ." ORDER BY id";
		$this -> _db -> setQuery($sql);
		if (!$this -> _db -> query()) {
			JError::raiseError(500, $this -> _db -> getErrorMsg());
		}
		return $this -> _db -> loadObjectList();
	}

	/**
	 * Method to get a single record.
	 * @param	integer	The id of the primary key.
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getTicket() {

		$tid = JRequest::getVar('tid');
		if (!empty($tid)) {
			$this -> _db -> setQuery("SELECT * FROM `#__imixtix_tickets` WHERE id = " . $tid);
			return $this -> _db -> loadObject();
		}
		return false;
	}

	/**
	 * Method to store a event.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	public function saveTicket($data) {
		//echo "<pre>"; print_r($data);echo "</pre>";exit;

		if (empty($data['id'])) {
			$data['created'] = date('Y-m-d H:i:s');
		}
		$data['modified'] = date('Y-m-d H:i:s');
		$table = JTable::getInstance('Tickets', 'ImixtixTable');
		// Bind the data.
		if (!$table -> bind($data)) {
			$this -> setError($table -> getError());
			return false;
		}
		// Check the data.
		if (!$table -> check()) {
			$this -> setError($table -> getError());
			return false;
		}
		// Store the data.
		if (!$table -> store()) {
			$this -> setError($table -> getError());
			return false;
		}
		return $table -> id;
	}

	/**
	 * Method to showHideTicket.
	 * @param	Ticket ID
	 * @return	true if success, false on failure
	 */
	public function showHideTicket() {

		$tid = JRequest::getVar('tid');
		$status = JRequest::getVar('status');
		if (isset($tid) && isset($status)) {
			$status = ($status == 1) ? 0 : 1;
			$sql = 'UPDATE #__imixtix_tickets SET status = ' . $status . ' WHERE id = ' . $tid;
			$this -> _db -> setQuery($sql);
			$this -> _db -> query();
			return true;
		}
	}

	/**
	 * Method to showHideTicket.
	 * @param	Ticket ID
	 * @return	true if success, false on failure
	 */
	public function deleteTicket($tid) {

            
            $Q= "select * from #__imixtix_salestickets WHERE  ticid='".$tid."'";
            $this -> _db -> setQuery($Q);
            $this -> _db -> query();
            $NumRows=$this -> _db -> getNumRows();
            if($NumRows==0)
            {
                if (isset($tid)) {
			$sql = 'DELETE FROM #__imixtix_tickets WHERE id = ' . $tid;
			$this -> _db -> setQuery($sql);
			$this -> _db -> query();
			return true;
		}
            }
            else
            {
			return false;
            }
            
            
            
//		$tid = JRequest::getVar('tid');
		
	}
	
	/**
	 * Method to retrieve event info.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	public function eventInfo($eventid) {
		
		if (!empty($eventid)) {
			$query = $this -> _db -> getQuery(true);
			$query -> select('a.*');
			$query -> from('`#__imixtix_events` AS a');
			// Join over the orders
			$query -> select('b.venue_street, b.venue_city, b.venue_state, b.venue_country, b.venue_zip');
			$query -> join('LEFT', '#__imixtix_venues AS b ON b.id = a.venue');
			// Join over the events
			$query -> where('a.id = ' . $eventid);
			$this -> _db -> setQuery($query);
			return $this -> _db -> loadObject();
		}
		return false;
	}

	/**
	 * Method to save comp tickets.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	public function sendTicketsSave($data) {


            $mainframe = JFactory::getApplication(); 
            $db=JFactory::getDBO();
            
            for($i=1;$i<=$data['Qty'];$i++)
            {
            $sql=" Insert into #__imixtix_comptickets  set orderid='".$data['orderid']."',ordernum='".$data['ordernum']."',ticketid='".$data['ticketid']."',userid='".$data['userid']."',eventid='".$data['eventid']."',quantity='".$data['quantity']."',email='".$data['email']."',firstname='".$data['firstname']."',lastname='".$data['lastname']."',Qty='".$data['Qty']."',barcodenum='".rand(1000000, 9999999)."'";
            if (empty($data['id'])) {
            $sql.=",created='".date('Y-m-d H:i:s')."'";
            }
            $sql.=",modified='".date('Y-m-d H:i:s')."'";

            $db->setQuery($sql);
            if($db->Query($sql))
                {
                $return=$data['ordernum'];
                }
                else
                {
                   $return='0';
                }
            }

             return  $return;
                
    
            
/*		$user = JFactory::getUser();
		$data ['userid'] = $user -> get('id');
		if (empty($data['id'])) {
			$data['created'] = date('Y-m-d H:i:s');
		}
		$data['modified'] = date('Y-m-d H:i:s');
		$table = JTable::getInstance('Comptickets', 'ImixtixTable');
		// Bind the data.
		if (!$table -> bind($data)) {
			$this -> setError($table -> getError());
			//return false;
		}
		// Check the data.
		if (!$table -> check()) {
			$this -> setError($table -> getError());
			//return false;
		}
		// Store the data.
		if (!$table -> store()) {
			$this -> setError($table -> getError());
			//return false;
		}
		//return true;	
*/
            
            
            
            }

            
            
            
            
            
            
            public function getDisclimerTicket()
    {
        $query= "Select * from #__imixtix_settings WHERE name='discilamer'";
        $this->_db->setQuery($query);
        $info= $this->_db->loadObject();
        return  $info->valuetbl;
        
    }
     
            
            
            
            
                
    
  public function createCompBarcodePDFTicket($data) 
    {
	
ini_set('max_execution_time', 300); 
	
       //$sql = "SELECT *  FROM `#__imixtix_comptickets` AS comp LEFT JOIN #__imixtix_events AS ev ON comp.eventid = ev.id
//Left join #__imixtix_venues as ven on ev.venue=ven.id WHERE comp.orderid ='".$data."'  ";
       
       
      $sql = " SELECT * FROM `#__imixtix_comptickets` AS comp  LEFT JOIN #__imixtix_events AS ev ON comp.eventid = ev.id
LEFT JOIN #__imixtix_venues AS ven ON ev.venue = ven.id  LEFT JOIN #__imixtix_tickets ON #__imixtix_tickets.id = comp.ticketid
WHERE comp.orderid = '".$data."'  ";
       
        $this -> _db -> setQuery($sql);
	$this -> _db -> Query($sql);
	$result = $this->_db->loadObjectList();


	
	
		
    require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/tcpdf/config/lang/eng.php';
    require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/tcpdf/tcpdf.php';
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	foreach($result as $item=>$value)
    {
    // create new PDF document
            $ticidi_order_number=$value->ordernum;  ##@ Order Number Of tickets
            $ticid=$value->ticketid;
            $eid =$value->eventid;
            $ticnumber =$value->barcodenum;
            $eventname =$value->event_title;
            $eventtime = ''; //$time;
            $eventsdate = date('l, F d,Y', strtotime($value -> start_date));
            $venue =$value -> venue_street . ', ' . $value -> venue_city . ', ' . $value -> venue_state . ', ' . $value -> venue_country;
            $email =$value->email;
            $price ='0';
            $firstname =$value->firstname;
            $lastname =$value->lastname;
            $username=isset ($value->firstname) ? isset ($value->lastname) ? ($value->firstname . ' ' . $value->lastname) : '' : '';

            $nticket =$value->Qty;
            $image1 =$value->image1;
            $image2 =$value->image2;
            //$street =$value->street;
            $city =$value-> venue_city;
            $state =$value->venue_state;
            $country =$value->venue_country;
            
            
            
            
            /********** Start of generating barcode ******************/
            $ticidi = $ticnumber ;
            $eventid = $eid;
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/BarcodeQR.php';
            // set BarcodeQR object
            $qr = new BarcodeQR();
            // create URL QR code
            $qr->text($ticidi);
            // display new QR code image

            if (!is_dir(JPATH_SITE . "/images/comptickets/barcode/qrcode")) {
		$oldmask = umask(0);
		 mkdir(JPATH_SITE . "/images/comptickets/barcode/qrcode", 0777, true);
			umask($oldmask);
            }
			
            $qr->draw(150, JPATH_SITE . "/images/comptickets/barcode/qrcode/" . $eventid . "_" . $ticidi);
            
            
            $ticidi = $ticnumber ;
            // Including all required classes
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgfontfile.php';
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgcolor.php';
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgdrawing.php';
            // Including the barcode technology
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgcode39.barcode.php';
            // Loading Font
            $font = new BCGFontFile('barcodegen/class/font/Arial.ttf', 18);
            // The arguments are R, G, B for color.
            $color_black = new BCGColor(0, 0, 0);
            $color_white = new BCGColor(255, 255, 255);
            
			/********** generating barcode -- HORIZONTAL ******************/
            $drawException = null;
            try {
                $code = new BCGcode39();
                $code->setScale(2); // Resolution
                $code->setThickness(30); // Thickness
                $code->setForegroundColor($color_black); // Color of bars
                $code->setBackgroundColor($color_white); // Color of spaces
                $code->setFont($font); // Font (or 0)
                $code->parse($ticidi); // Text
            } catch (Exception $exception) {
                $drawException = $exception;
            }
            // Here is the list of the arguments
            //1 - Filename (empty : display on screen)
            //2 - Background color
            $drawing = new BCGDrawing(JPATH_SITE . '/images/comptickets/barcode/Event_Ticket_Bar_' . $ticidi . '.png', $color_white);
            if ($drawException) {
                $drawing->drawException($drawException);
            } else {
                $drawing->setBarcode($code);
                $drawing->draw();
            }
            // Header that says it is an image (remove it if you save the barcode to a file)
            // header('Content-Type: image/png');
            // Draw (or save) the image into PNG format.
            $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
            //print_r($drawing);

            /********** generating barcode -- VERTICAL ******************/
            $drawException1 = null;
            try {
                $code1 = new BCGcode39();
                $code1->rotationAngle = 90;
                $code1->setScale(2); // Resolution
                $code1->setThickness(30); // Thickness
                $code1->setForegroundColor($color_black); // Color of bars
                $code1->setBackgroundColor($color_white); // Color of spaces
                $code1->setFont($font); // Font (or 0)
                $code1->parse($ticidi); // Text
            } catch (Exception $exception1) {
                $drawException1 = $exception1;
            }
            // Here is the list of the arguments
            //1 - Filename (empty : display on screen)
            //2 - Background color
            if (!is_dir(JPATH_SITE . "/images/comptickets/barcode/vertical")) {
		$oldmask = umask(0);
                mkdir(JPATH_SITE . "/images/comptickets/barcode/vertical", 0777, true);
            umask($oldmask);
		}
            $drawing1 = new BCGDrawing(JPATH_SITE . '/images/comptickets/barcode/vertical/Event_Ticket_Bar_' . $ticidi . '.png', $color_white, 90.0);
            if ($drawException1) {
                $drawing1->drawException($drawException1);
            } else {
                $drawing1->setBarcode($code1);
                $drawing1->draw();
            }
            // Header that says it is an image (remove it if you save the barcode to a file)
            //header('Content-Type: image/png');
            // Draw (or save) the image into PNG format.
            $drawing1->finish(BCGDrawing::IMG_FORMAT_PNG);
            //echo "<PRE>"; print_r($drawing); exit;
       /********** End of generating barcode ******************/

            
	 
	  
	     

            
            
            /********** PDF Generation for each Ticket ******************/
            $ticidi = $ticnumber ;
            // Add a page
            // This method has several options, check the source code documentation for more information.
            $pdf->AddPage();
            $qrcodename = $eventid . '_' . $ticidi;
            // set Rotate
            $params = $pdf->serializeTCPDFtagParameters(array(270));
            // other configs
            $pdf->setOpenCell(9);
            $pdf->SetCellPadding(9);
            $pdf->setCellHeightRatio(1.25);
            //$pdf->setFontSpacing(1.25);
            $pdf->setFontStretching(120);
            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Imixtix');
            $pdf->SetTitle('Event Tickets');
            $pdf->SetSubject('Event Tickets');
            $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
            // set default header data
            //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            //set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            //set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            //set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            //set some language-dependent strings
            $pdf->setLanguageArray($l);
            // ---------------------------------------------------------
            // set default font subsetting mode
            $pdf->setFontSubsetting(true);
            // Set font
            // dejavusans is a UTF-8 Unicode font, if you only need to
            // print standard ASCII chars, you can use core fonts like
            // helvetica or times to reduce file size.
            //$pdf->SetFont('arial', '', 14, '', true); //dejavusans

            
             $PurchasedTicket=$value->tic_name;
         

            
            $systemrecord=$this->getDisclimerTicket();

            
            $html = '';

            $html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><m-eta http-equiv="Content-Type" content="text/html; charset=utf-8" /><t-itle>Ticket::' . $eventname . '</title></head>';

            $html .= '<body><table width="600" border="0" cellspacing="0" cellpadding="0"><tr><td width="200" valign="top"><img src="' .$this->getLogo(). '"  height="49" alt="" /></td><td valign="top" width="398"><table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td  valign="top" style="font-size: 22px;text-transform: uppercase;color: #000; padding: 5px 5px 5px 10px; background-color: #D9D9D9;line-height: 4px;">' . strtoupper("This is your ticket.You must present the entire page to gain admittance to event. <br />Duplication of this ticket may result in refusal of entry.") . '</td></tr></table></td></tr></table><table width="583" border="0" cellspacing="4" cellpadding="0"  bgcolor="#D9D9D9"  border-color="#FFFFFF;" style="padding: 5px 5px 5px 10px;"><tr><td width="200" valign="top" style="text-align:center">';

			if (!empty($image1)) {
				$html .= '<img  margin-top: "7px"; margin-bottom:"13px"; margin-left:"7px;" src="'.JURI::base() . 'components/com_imixtix/includes/timthumb.php?src='.JURI::root() . '/images/imixtix_events/' . $image1 . '&h=210&w=200&zc=0"  alt="" />';
			} else {
				$html .= '<img  margin-top: "7px"; margin-bottom:"13px"; margin-left:"7px;" src="'. IMG_URL . 'noimage.jpg"  alt="No Image available" />';
			}
                        
                        $ticket_price = !empty ($price) ? $price : '0'; 
            $html .= '<br /><img src="' . JURI::root() . '/images/comptickets/barcode/qrcode/' . $qrcodename . '.png"  width="100px" height="100px" alt="" /></td><td width="300" valign="top" bgcolor="#ffffff"><h1 style="font-size: 54px;font-weight: bold;margin: 0px; margin-top:10px;padding: 0px; padding-right:10px; line-height:29px;"> ' . $eventname . '</h1><div style="font-size: 32px; padding-top: 8px;"> ' . strtoupper($eventsdate) . '</div><table style="border-top: 3px solid #D9D9D9; width="250"" bgcolor="#D9D9D9" border-color="#FFFFFF" border="0" cellspacing="0" cellpadding="9"><tr><td  style="border-bottom:1px #333 solid; font-size: 28px;" valign="top">Name</td><td align="right" valign="top" nowrap="nowrap" border=" TRBL" style="border-bottom:1px #333 solid; font-size: 28px;">' . $firstname . '  ' . $lastname . '</td></tr> 
						<tr><td valign="top" style="border-bottom-width: 5px; border-bottom-style: solid; border-bottom-color: #D9D9D9; font-size: 28px;">Order Number</td><td align="right" valign="top" style="border-bottom-width: 1px;	border-bottom-style: solid;	border-bottom-color: #D9D9D9;font-size: 10px;"><span  style="font-size:30px">' . $ticidi_order_number . '</span></td></tr>

<tr><td valign="top" style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #D9D9D9; font-size: 28px;">Price</td><td align="right" valign="top" style="border-bottom-width: 1px;	border-bottom-style: solid;	border-bottom-color: #D9D9D9;font-size: 28px;">$ ' . $ticket_price . '</td></tr>

<tr><td valign="top" style="border-bottom-width: 1px;	border-bottom-style: solid;	border-bottom-color: #D9D9D9;font-size: 28px;">Purchase date</td><td align="right" valign="top"  style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #D9D9D9;font-size: 28px;">' . date("F j, Y") . '</td></tr>

			<tr><td valign="top"  style="font-size: 40px; font-weight: bold;">'.$PurchasedTicket.'</td><td align="right" valign="top" style="padding-top:30px"></td></tr></table></td><td bgcolor="#ffffff" align="center" width="81" valign="middle"><table><tr><td width="20%"  method="Test" params="' . $params . '" ></td><td  width="80%" ><p style="color:#FFF">12</p><img  alt="" height ="225" src="' . JURI::root() . '/images/comptickets/barcode/vertical/Event_Ticket_Bar_' . $ticidi . '.png"/></td></tr></table></td></tr></table><div style="height: 5px;width: 100px; bgcolor="#FFFFFF""></div><table width="600" bgcolor="#D9D9D9" border="0" cellspacing="1" cellpadding="0" border-color="#FFFFFF;"><tr><td valign="top" bgcolor="#FFFFFF" style="font-size: 18px;	color: #000;text-align: justify; padding-top: 10px;padding-right: 20px;padding-bottom: 10px; padding-left: 20px;"><strong>Ticket Disclaimer/policy info: </strong><p>Any ticket issued is subject to the following conditions and may be revoked by the issuer, proprietor of the venue and/or organiser of the event for breach of any of the specified conditions:</p>
			'.            (!empty ($systemrecord)? $systemrecord : $default).'</td></tr><tr><td valign="top" bgcolor="#FFFFFF" style="font-size: 11px;	color: #000;text-align: justify;	padding-top: 10px; padding-right: 20px; padding-bottom: 10px; padding-left: 20px;"><table border="0"  cellpadding="0" cellspacing="0" bgcolor="#FFFFFF"><tbody><tr> <td width="250" align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="' . JURI::root() . '/images/comptickets/barcode/qrcode/' . $qrcodename . '.png" align="left" width="80" height="80" alt="" /></td><td width"81" align="right" style="font-size:30px; text-align:center;"><br /><table border="0"  cellpadding="0" cellspacing="0" bgcolor="#FFFFFF"><tbody><tr><td><span style="padding-top:30px;"><br />&nbsp;<img  style="margin-top:50px;" src="' . JURI::root() . '/images/comptickets/barcode/Event_Ticket_Bar_' . $ticidi . '.png" height="40" width="200" alt="" /></span></td></tr><tr><td style="text-align:center; font-size:40px;margin-right:60px;"><span style="margin-right:60px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $ticidi . '</span></td></tr></tbody></table></td></tr></tbody></table></td></tr></table>';

            $html .= '</body></html>';
            // Print text using writeHTMLCell()
			$pdf->writeHTML($html);
            // ---------------------------------------------------------
            // Close and output PDF document
            // This method has several options, check the source code documentation for more information.
            $kticid = $ticidi;
            // array with names of columns
            $arr_nomes = array(
                array("$kticid", 169, 56), // array("$kticid", 174, 60) // array(name, new X, new Y);
            );
            // num of pages
           // $ttPages = $pdf->getNumPages();
           // for ($ii = 1; $ii < 2; $ii++) {
                // set page
               // $pdf->setPage($ii);
                // all columns of current page
                foreach ($arr_nomes as $num => $arrCols) {

                    $x = $pdf->xywalter[$num][0] + $arrCols[1];
                    // new X
                    $y = $pdf->xywalter[$num][1] + $arrCols[2];
                    // new Y
                    $n = $arrCols[0];
                    // column name
                    // transforme Rotate
                    $pdf->StartTransform();
                    // Rotate 90 degrees counter-clockwise
                    $pdf->Rotate(270, $x, $y);
                    $pdf->Text($x, $y, $n);
                    // Stop Transformation
                    $pdf->StopTransform();
                }
        /********** PDF Generation for each Ticket ******************/
      
            
      }
      
      
      
        $pdf_name=time().".pdf";
        $updatepdfsalestbl="update   #__imixtix_comptickets set is_pdf='".$pdf_name."'  WHERE ordernum = '" . $data . "'";
        $this -> _db -> setQuery($updatepdfsalestbl);
	$this -> _db -> Query($updatepdfsalestbl);
	
      	
        
                
	$maildata ="SELECT * FROM `#__imixtix_comptickets` AS comp right join #__imixtix_events as events on comp.eventid=events.id inner join #__imixtix_venues as venues on events.venue=venues.id WHERE comp.ordernum = '" . $data . "' LIMIT 1";
        $this -> _db -> setQuery($maildata);
	$this -> _db -> Query($maildata);
	$mailinfo = $this->_db->loadObject();
        


        $mailinfo->start_date;
        $mailinfo->quantity;
//        $venue=$mailinfo->venue_name.$mailinfo->venue_desc.$mailinfo->venue_region.$mailinfo->venue_country.$mailinfo->venue_state.$mailinfo->venue_zip;

   $venue=$mailinfo->venue_name."*".$mailinfo->venue_city;

        $username=isset ($mailinfo->firstname) ? isset ($mailinfo->lastname) ? ($mailinfo->firstname . ' ' . $mailinfo->lastname) : '' : '';
        $pdf->Output(JPATH_SITE . "/images/comptickets/" . $pdf_name, "F");
        $this->mailCreatedCompBarcodePDFTicket($mailinfo->email, $pdf_name, $username, $mailinfo->event_title, $mailinfo->eventid,$mailinfo->start_date,$mailinfo->quantity,$venue,$data);
        return true;
   
      
     
	
        
       
		
		 
}
    


            
	
        
        
        
            /**
     * Method to send email with ticket.
     * @param	array
     * @return	mixed	True on success.
     */
    function mailCreatedCompBarcodePDFTicket($email,$pdfname,$username, $eventname, $eventid,$start_date,$quantity,$venue) {
        
        $config = JFactory::getConfig();


		$dtEvent=date('l M d Y',strtotime($start_date));


		$venues= explode("*",$venue);
		$venuename=$venues[0];
		$venuecity=$venues[1];

    
        ####@@ Email Template Call Here  
        $tplparams=$this->getEmailTpl('3');
       
        ########@@ Check if  any cc exist 
        $cc=(isset($tplparams->cc) && !empty($tplparams->cc)?$tplparams->cc:null);
        
        ###@Check If  any bcc  exist 
        $bcc=(isset($tplparams->bcc) && !empty($tplparams->bcc)?$tplparams->bcc:null);
        
        #######@@ check if  any from exist or not 
        
        $from=(isset($tplparams->fromemail) && !empty($tplparams->fromemail)?$tplparams->fromemail:JText::_('COM_IMIXTIX_FROM_EMAIL'));
        
        
        $fromname = $config->get('fromname');
        
        $attachment = JPATH_SITE . "/images/comptickets/" . $pdfname;
    
        
        $recipient = $email;
           
        
        
        
        #########################Subject filter here start #############################
        if(isset($tplparams->subject) && !empty($tplparams->subject))
        {
            ##@Event name 
            if(preg_match('{EVENTSNAME}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{EVENTSNAME}',$eventname,$tplparams->subject);
            }
            
            ##@Event id
            if(preg_match('{EVENTID}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{EVENTID}',$eventid,$tplparams->subject);
            }
            
            ###@Event start date
            if(preg_match('{EVENTSDATE}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{EVENTSDATE}',$dtEvent,$tplparams->subject);
            }
            
            ####@@Event buyer name 
            if(preg_match('{BUYERNAME}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{BUYERNAME}',ucfirst($username),$tplparams->subject);
            }
            
            ######@Event notifaction Date 
            if(preg_match('{NOTIFICATIONDATE}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{NOTIFICATIONDATE}',date('d M, Y'),$tplparams->subject);
            }
            
            ####@if Evenet Quantity
            if(preg_match('{QUANTITY}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{QUANTITY}',$quantity,$tplparams->subject);
            }
            
           
            ####@@Event Venue name
            if(preg_match('{VENUE_NAME}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{VENUE_NAME}',$venuename,$tplparams->subject);
            }

	    ####@@Event Venue city
            if(preg_match('{VENUE_CITY}',$tplparams->subject)==true)
            {
            $subjectTxt= preg_replace('{VENUE_CITY}',$venuecity,$tplparams->subject);
            }
        }
        else
        {
           $subjectTxt = JText::_('COM_IMIXTIX_EVENT_TICKETS') . ' - ' . $eventname;
        }
        #########################Subject filter here end #############################        
       
        
        
        
        
        
        
        #########################body filter here start ############################# 
        $body=$tplparams->tpl;
            ####### chang elogo here 
            if(preg_match('{LOGO}',$body))
            {
            $bodypart=preg_replace('{LOGO}','<img border="0" alt="islandmix" src="' .$this->getLogo().'">',$body);
            }
    
            ##@Event name 
            if(preg_match('{EVENTSNAME}',$body)==true)
            {
                $bodypart=preg_replace('{EVENTSNAME}', $eventname,$bodypart);
            }
            
            ##@Event id
            if(preg_match('{EVENTID}',$body)==true)
            {
                $bodypart= preg_replace('{EVENTID}',$eventid,$bodypart);
            }
            
            ###@Event start date
            if(preg_match('{EVENTSDATE}',$body)==true)
            {
                $bodypart= preg_replace('{EVENTSDATE}',$dtEvent,$bodypart);
            }
            
            ####@@Event buyer name 
            if(preg_match('{BUYERNAME}',$body)==true)
            {
                $bodypart=preg_replace('{BUYERNAME}', ucfirst($username),$bodypart);
            }
            
            ######@Event notifaction Date 
            if(preg_match('{NOTIFICATIONDATE}',$body)==true)
            {
                $bodypart=preg_replace('{NOTIFICATIONDATE}','<br/>'.date('d M, Y'),$bodypart);
            }
            
            ####@if Evenet Quantity
            if(preg_match('{QUANTITY}',$body)==true)
            {
                $bodypart= preg_replace('{QUANTITY}',$quantity,$bodypart);
            }
            
             ####@@Event Venue name
            if(preg_match('{VENUE_NAME}',$body)==true)
            {
            $bodypart= preg_replace('{VENUE_NAME}',$venuename,$bodypart);
            }

	    ####@@Event Venue city
            if(preg_match('{VENUE_CITY}',$body)==true)
            {
            $bodypart= preg_replace('{VENUE_CITY}',$venuecity,$bodypart);
            }

        #########################body filter here end ############################# 
        
        
        
        /****************Clean curly braces here *************/        
        $subjectTxt=str_replace('{','',$subjectTxt);
        $subjectTxt=str_replace('}','',$subjectTxt);
        $bodypart=str_replace('{','',$bodypart);
        $bodypart=str_replace('}','',$bodypart);
        /****************Clean curly braces here *************/        
    
        
        
        /****************send mail here *************/        
        JUtility::sendMail($from, $fromname, $recipient, $subjectTxt, $bodypart, 1, $cc, $bcc, $attachment);
	$this->deletecomplimentryfiles($data,$attachment);
        return true;
    }



    
    
    
    
    /**************************************
     ***********Function get Email Template *
     *****************************************/
    public function getEmailTpl($type_id)
    {
        
        $sql="select * from #__imixtix_settings_email_temlates where id='".$type_id."'";
        $this -> _db -> setQuery($sql);
	$this -> _db -> Query($sql);
	
        
        $result=$this->_db->loadObject();
       
        return $this->_db->loadObject();
        
        
        
    }    
    
    
    
    
    /**************** Function To make Sales  PDF  Here & Download ************/
    ############################################################################
    function salePdf()
    {
ini_set('max_execution_time', 300); 

        $data = JRequest::get('request');
       
$sql="SELECT * FROM #__imixtix_salestickets sal LEFT JOIN #__imixtix_tickets AS tik ON tik.id = sal.ticid 
        WHERE sal.trackingid = '" . $data['oid'] . "'";



// $sql = "SELECT *  FROM #__imixtix_salestickets WHERE trackingid = '" . $data['oid'] . "'";
	$this -> _db -> setQuery($sql);
	$this -> _db -> Query($sql);
	$result = $this->_db->loadObjectList();
		
	require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/tcpdf/config/lang/eng.php';
        require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/tcpdf/tcpdf.php';
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	foreach($result as $item=>$value)
        {
            // create new PDF document
            $ticidi_order_number=$value->trackingid;  ##@ Order Number Of tickets
            $ticid=$value->ticid;
            $eid =$value->eventid;
            $ticnumber =$value->barcodenum;
            $eventname =$value->eventname;
            $eventtime = ''; 
            $eventsdate =  $value->eventsdate;
            $venue =$value->venue;
            $email =$value->buyeremail;
	    $price =$value->ticket_price;
            $firstname =$value->first_name;
            $lastname =$value->last_name;
            $username =$value->username;
            $nticket =$value->Qty;
            $image1 =$value->image1;
            $image2 =$value->image2;
            $city =$value->city;
            $state =$value->state;
            $country =$value->country;
            
            
            
            
            /********** Start of generating barcode ******************/
            $ticidi = $ticnumber ;
            $eventid = $eid;
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/BarcodeQR.php';
            // set BarcodeQR object
            $qr = new BarcodeQR();
            // create URL QR code
            $qr->text($ticidi);
            // display new QR code image
            if (!is_dir(JPATH_SITE . "/images/salestickets/barcode/qrcode")) {
                mkdir(JPATH_SITE . "/images/salestickets/barcode/qrcode", 0777, true);
            }
            $qr->draw(150, JPATH_SITE . "/images/salestickets/barcode/qrcode/" . $eventid . "_" . $ticidi);
            
            
            
            $ticidi = $ticnumber ;
            // Including all required classes
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgfontfile.php';
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgcolor.php';
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgdrawing.php';
            // Including the barcode technology
            require_once JPATH_COMPONENT_ADMINISTRATOR . '/includes/barcodegen/class/bcgcode39.barcode.php';
            // Loading Font
            $font = new BCGFontFile('barcodegen/class/font/Arial.ttf', 18);
            // The arguments are R, G, B for color.
            $color_black = new BCGColor(0, 0, 0);
            $color_white = new BCGColor(255, 255, 255);
            
			/********** generating barcode -- HORIZONTAL ******************/
            $drawException = null;
            try {
                $code = new BCGcode39();
                $code->setScale(2); // Resolution
                $code->setThickness(30); // Thickness
                $code->setForegroundColor($color_black); // Color of bars
                $code->setBackgroundColor($color_white); // Color of spaces
                $code->setFont($font); // Font (or 0)
                $code->parse($ticidi); // Text
            } catch (Exception $exception) {
                $drawException = $exception;
            }
            // Here is the list of the arguments
            //1 - Filename (empty : display on screen)
            //2 - Background color
            $drawing = new BCGDrawing(JPATH_SITE . '/images/salestickets/barcode/Event_Ticket_Bar_' . $ticidi . '.png', $color_white);
            if ($drawException) {
                $drawing->drawException($drawException);
            } else {
                $drawing->setBarcode($code);
                $drawing->draw();
            }
            // Header that says it is an image (remove it if you save the barcode to a file)
            // header('Content-Type: image/png');
            // Draw (or save) the image into PNG format.
            $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
            //print_r($drawing);

            /********** generating barcode -- VERTICAL ******************/
            $drawException1 = null;
            try {
                $code1 = new BCGcode39();
                $code1->rotationAngle = 90;
                $code1->setScale(2); // Resolution
                $code1->setThickness(30); // Thickness
                $code1->setForegroundColor($color_black); // Color of bars
                $code1->setBackgroundColor($color_white); // Color of spaces
                $code1->setFont($font); // Font (or 0)
                $code1->parse($ticidi); // Text
            } catch (Exception $exception1) {
                $drawException1 = $exception1;
            }
            // Here is the list of the arguments
            //1 - Filename (empty : display on screen)
            //2 - Background color
            if (!is_dir(JPATH_SITE . "/images/salestickets/barcode/vertical")) {
                mkdir(JPATH_SITE . "/images/salestickets/barcode/vertical", 0777, true);
            }
            $drawing1 = new BCGDrawing(JPATH_SITE . '/images/salestickets/barcode/vertical/Event_Ticket_Bar_' . $ticidi . '.png', $color_white, 90.0);
            if ($drawException1) {
                $drawing1->drawException($drawException1);
            } else {
                $drawing1->setBarcode($code1);
                $drawing1->draw();
            }
            // Header that says it is an image (remove it if you save the barcode to a file)
            //header('Content-Type: image/png');
            // Draw (or save) the image into PNG format.
            $drawing1->finish(BCGDrawing::IMG_FORMAT_PNG);
            //echo "<PRE>"; print_r($drawing); exit;
       /********** End of generating barcode ******************/

            
	 
	  
	     

            
            
            /********** PDF Generation for each Ticket ******************/
            $ticidi = $ticnumber ;
            
            // Add a page
            // This method has several options, check the source code documentation for more information.
            $pdf->AddPage();
            $qrcodename = $eventid . '_' . $ticidi;
            // set Rotate
            $params = $pdf->serializeTCPDFtagParameters(array(270));
            // other configs
            $pdf->setOpenCell(9);
            $pdf->SetCellPadding(9);
            $pdf->setCellHeightRatio(1.25);
            //$pdf->setFontSpacing(1.25);
            $pdf->setFontStretching(120);
            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Imixtix');
            $pdf->SetTitle('Event Tickets');
            $pdf->SetSubject('Event Tickets');
            $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
            // set default header data
            //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            //set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            //set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            //set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            //set some language-dependent strings
            $pdf->setLanguageArray($l);
            // ---------------------------------------------------------
            // set default font subsetting mode
            $pdf->setFontSubsetting(true);
            // Set font
            // dejavusans is a UTF-8 Unicode font, if you only need to
            // print standard ASCII chars, you can use core fonts like
            // helvetica or times to reduce file size.
            //$pdf->SetFont('arial', '', 14, '', true);//dejavusans



		$PurchasedTicket=$value->tic_name;



		$systemrecord=$this->getDisclimerTicket();


            $html = '';

            $html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><m-eta http-equiv="Content-Type" content="text/html; charset=utf-8" /><t-itle>Ticket::' . $eventname . '</title></head>';

            $html .= '<body><table width="600" border="0" cellspacing="0" cellpadding="0"><tr><td width="200" valign="top"><img src="' .  $this->getLogo().'"  height="49" alt="" /></td><td valign="top" width="398"><table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td  valign="top" style="font-size: 22px;text-transform: uppercase;color: #000; padding: 5px 5px 5px 10px; background-color: #D9D9D9;line-height: 4px;">' . strtoupper("This is your ticket.You must present the entire page to gain admittance to event. <br />Duplication of this ticket may result in refusal of entry.") . '</td></tr></table></td></tr></table><table width="583" border="0" cellspacing="4" cellpadding="0"  bgcolor="#D9D9D9"  border-color="#FFFFFF;" style="padding: 5px 5px 5px 10px;"><tr><td width="200" valign="top" style="text-align:center">';

            if (!empty($image1)) {
                $html .= '<img  margin-top: "7px"; margin-bottom:"13px"; margin-left:"7px;" src="' . JURI::root() . 'administrator/components/com_imixtix/includes/timthumb.php?src=' . JURI::root() . '/images/imixtix_events/' . $image1 . '&h=210&w=200&zc=0"  alt="" />';
            } else {
                $html .= '<img  margin-top: "7px"; margin-bottom:"13px"; margin-left:"7px;" src="' . IMG_URL . 'noimage.jpg"  alt="No Image available" />';
            }
			$ticket_price = !empty ($price) ? $price : '0'; 
            $html .= '<br /><img src="' . JURI::root() . '/images/salestickets/barcode/qrcode/' . $qrcodename . '.png"  width="100px" height="100px" alt="" /></td><td width="300" valign="top" bgcolor="#ffffff"><h1 style="font-size: 54px;font-weight: bold;margin: 0px; margin-top:10px;padding: 0px; padding-right:10px; line-height:29px;"> ' . $eventname . '</h1><div style="font-size: 32px; padding-top: 8px;"> ' . strtoupper($eventsdate) . '</div><table style="border-top: 3px solid #D9D9D9; width="250"" bgcolor="#D9D9D9" border-color="#FFFFFF" border="0" cellspacing="0" cellpadding="9"><tr><td  style="border-bottom:1px #333 solid; font-size: 28px;" valign="top">Name</td><td align="right" valign="top" nowrap="nowrap" border=" TRBL" style="border-bottom:1px #333 solid; font-size: 28px;">' . $firstname . '  ' . $lastname . '</td></tr> 
			
			<tr><td valign="top" style="border-bottom-width: 5px; border-bottom-style: solid; border-bottom-color: #D9D9D9; font-size: 28px;">Order Number</td><td align="right" valign="top" style="border-bottom-width: 1px;	border-bottom-style: solid;	border-bottom-color: #D9D9D9;font-size: 10px;"><span  style="font-size:30px">' . $ticidi_order_number . '</span></td></tr>

<tr><td valign="top" style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #D9D9D9; font-size: 28px;">Price</td>
<td align="right" valign="top" style="border-bottom-width: 1px;	border-bottom-style: solid;	border-bottom-color: #D9D9D9;font-size: 28px;">$ ' . $ticket_price . '</td></tr>


<tr><td valign="top" style="border-bottom-width: 1px;	border-bottom-style: solid;	border-bottom-color: #D9D9D9;font-size: 28px;">Purchase date</td><td align="right" valign="top"  style="border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #D9D9D9;font-size: 28px;">' . date("F j, Y") . '</td></tr>
			<tr><td valign="top"  style="font-size: 40px; font-weight: bold;">'.$PurchasedTicket.'</td><td align="right" valign="top" style="padding-top:30px"></td></tr></table></td><td bgcolor="#ffffff" align="center" width="81" valign="middle"><table><tr><td width="20%"  method="Test" params="' . $params . '" ></td><td  width="80%" ><p style="color:#FFF">12</p><img  alt="" height ="225" src="' . JURI::root() . '/images/salestickets/barcode/vertical/Event_Ticket_Bar_' . $ticidi . '.png"/></td></tr></table></td></tr></table><div style="height: 5px;width: 100px; bgcolor="#FFFFFF""></div><table width="600" bgcolor="#D9D9D9" border="0" cellspacing="1" cellpadding="0" border-color="#FFFFFF;"><tr><td valign="top" bgcolor="#FFFFFF" style="font-size: 18px;	color: #000;text-align: justify; padding-top: 10px;padding-right: 20px;padding-bottom: 10px; padding-left: 20px;"><strong>Ticket Disclaimer/policy info: </strong><p>Any ticket issued is subject to the following conditions and may be revoked by the issuer, proprietor of the venue and/or organiser of the event for breach of any of the specified conditions:</p>
			'.(!empty ($systemrecord)? $systemrecord : $default).'
			</td></tr><tr><td valign="top" bgcolor="#FFFFFF" style="font-size: 11px;	color: #000;text-align: justify;	padding-top: 10px; padding-right: 20px; padding-bottom: 10px; padding-left: 20px;"><table border="0"  cellpadding="0" cellspacing="0" bgcolor="#FFFFFF"><tbody><tr> <td width="250" align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="' . JURI::root() . '/images/salestickets/barcode/qrcode/' . $qrcodename . '.png" align="left" width="80" height="80" alt="" /></td><td width"81" align="right" style="font-size:30px; text-align:center;"><br /><table border="0"  cellpadding="0" cellspacing="0" bgcolor="#FFFFFF"><tbody><tr><td><span style="padding-top:30px;"><br />&nbsp;<img  style="margin-top:50px;" src="' . JURI::root() . '/images/salestickets/barcode/Event_Ticket_Bar_' . $ticidi . '.png" height="40" width="200" alt="" /></span></td></tr><tr><td style="text-align:center; font-size:40px;margin-right:60px;"><span style="margin-right:60px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $ticidi . '</span></td></tr></tbody></table></td></tr></tbody></table></td></tr></table>';

            $html .= '</body></html>';
            // Print text using writeHTMLCell()
            $pdf->writeHTML($html);
            // ---------------------------------------------------------
            // Close and output PDF document
            // This method has several options, check the source code documentation for more information.
            $kticid = $ticidi;
            // array with names of columns
            $arr_nomes = array(
                array("$kticid", 169, 56), // array("$kticid", 174, 60) // array(name, new X, new Y);
            );
            // num of pages
           // $ttPages = $pdf->getNumPages();
           // for ($ii = 1; $ii < 2; $ii++) {
                // set page
               // $pdf->setPage($ii);
                // all columns of current page
              
  foreach ($arr_nomes as $num => $arrCols) { 

                    $x = $pdf->xywalter[$num][0] + $arrCols[1];
                    // new X
                    $y = $pdf->xywalter[$num][1] + $arrCols[2];
                    // new Y
                    $n = $arrCols[0];
                    // column name
                    // transforme Rotate
                    $pdf->StartTransform();
                    // Rotate 90 degrees counter-clockwise
                    $pdf->Rotate(270, $x, $y);
                    $pdf->Text($x, $y, $n);
                    // Stop Transformation
                    $pdf->StopTransform();
                }
        /********** PDF Generation for each Ticket ******************/
      }
        $pdf_name=time().".pdf";
	$pdf->Output(JPATH_SITE . "/images/salestickets/" . $pdf_name, "F");
        return  $pdf_name;

     }
    
    






/***************########File Downlaod  Function Start Here  ********************/
  

/***************########File Downlaod  Function Start Here  ********************/
    function output_file($file, $name, $mime_type='')
{
 /*
 This function takes a path to a file to output ($file),  the filename that the browser will see ($name) and  the MIME type of the file ($mime_type, optional).
 */
 
 //Check the file premission
 if(!is_readable($file)) die('File not found or inaccessible!');
 
 $size = filesize($file);
 $name = rawurldecode($name);
 
 /* Figure out the MIME type | Check in array */
 $known_mime_types=array(
 	"pdf" => "application/pdf",
 	"txt" => "text/plain",
 	"html" => "text/html",
 	"htm" => "text/html",
	"exe" => "application/octet-stream",
	"zip" => "application/zip",
	"doc" => "application/msword",
	"xls" => "application/vnd.ms-excel",
	"ppt" => "application/vnd.ms-powerpoint",
	"gif" => "image/gif",
	"png" => "image/png",
	"jpeg"=> "image/jpg",
	"jpg" =>  "image/jpg",
	"php" => "text/plain"
 );
 
 if($mime_type==''){
	 $file_extension = strtolower(substr(strrchr($file,"."),1));
	 if(array_key_exists($file_extension, $known_mime_types)){
		$mime_type=$known_mime_types[$file_extension];
	 } else {
		$mime_type="application/force-download";
	 };
 };
 
 //turn off output buffering to decrease cpu usage
 @ob_end_clean(); 
 
 // required for IE, otherwise Content-Disposition may be ignored
 if(ini_get('zlib.output_compression'))
  ini_set('zlib.output_compression', 'Off');
 
 header('Content-Type: ' . $mime_type);
 header('Content-Disposition: attachment; filename="'.$name.'"');
 header("Content-Transfer-Encoding: binary");
 header('Accept-Ranges: bytes');
 
 /* The three lines below basically make the 
    download non-cacheable */
 header("Cache-control: private");
 header('Pragma: private');
 header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
 
 // multipart-download and download resuming support
 if(isset($_SERVER['HTTP_RANGE']))
 {
	list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
	list($range) = explode(",",$range,2);
	list($range, $range_end) = explode("-", $range);
	$range=intval($range);
	if(!$range_end) {
		$range_end=$size-1;
	} else {
		$range_end=intval($range_end);
	}
	/*
	------------------------------------------------------------------------------------------------------
	//This application is developed by www.webinfopedia.com
	//visit www.webinfopedia.com for PHP,Mysql,html5 and Designing tutorials for FREE!!!
	------------------------------------------------------------------------------------------------------
 	*/
	$new_length = $range_end-$range+1;
	header("HTTP/1.1 206 Partial Content");
	header("Content-Length: $new_length");
	header("Content-Range: bytes $range-$range_end/$size");
 } else {
	$new_length=$size;
	header("Content-Length: ".$size);
 }
 
 /* Will output the file itself */
 $chunksize = 1*(1024*1024); //you may want to change this
 $bytes_send = 0;
 if ($file = fopen($file, 'r'))
 {
	if(isset($_SERVER['HTTP_RANGE']))
	fseek($file, $range);
 
	while(!feof($file) && 
		(!connection_aborted()) && 
		($bytes_send<$new_length)
	      )
	{
		$buffer = fread($file, $chunksize);
		print($buffer); //echo($buffer); // can also possible
		flush();
		$bytes_send += strlen($buffer);
	}
 fclose($file);
 } else
 //If no permissiion
 die('Error - can not open file.');
//die
die();
}



    
    
    /**************** Function To make Sales  PDF  Here & Download ************/
    ############################################################################
    function downloadpdf($oid,$name)
    {
	$file=JPATH_SITE . "/images/salestickets/".$name ;
	$this->output_file($file,$name,$mime_type='application/pdf');
	$this->deleteimagesPdf($oid,$file);
}
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    function getLogo()
    {
        
        $logoquery="select * from #__imixtix_settings where name='upload_logo' ";
        $this -> _db -> setQuery($logoquery);
        $this -> _db -> Query($logoquery);
        $result_logo = $this->_db->loadObjectList();
        $logo=JURI::base().'components/com_imixtix/assets/images/logo/'.$result_logo[0]->valuetbl;
        //return IMG_URL.'imixlogoweb.jpg';
	return  $logo;
        
    }
    
    
    
    
    
    
    
    


    function getbarcode($id,$trackingcode)
    {
        
         $sql="select * from #__imixtix_salestickets where id='".$id."' and  trackingid='".$trackingcode."'";
         $this -> _db ->setQuery($sql);
         $this -> _db -> Query();
         $info= $this -> _db -> loadObject();
         return  $info->barcodenum;
        
    }
    
    
    
    function getTicketPrice($ticid)
    {
        $price_individualticket='';
        $query="select * from #__imixtix_tickets where id='".$ticid."'";
        $this -> _db ->setQuery($query);
        $this -> _db -> Query($query);
        $info= $this -> _db -> loadObject();
        return $info->total_price;
        
        
    }
    
    
    
    
    function barcodeDelete($trackingcode)
    {
        
        $query= "select * from #__imixtix_salestickets WHERE trackingid='".$trackingcode."'";
        $this -> _db ->setQuery($query);
        $this -> _db -> Query($query);
        $info= $this -> _db -> loadObjectlist();
        

        foreach($info as $keys=>$items)
        {
            
             unlink(JPATH_SITE . "/images/salestickets/barcode/qrcode/" . $items->eventid . "_" . $items->barcodenum.".png");
             unlink(JPATH_SITE . "/images/salestickets/barcode/Event_Ticket_Bar_" .  $items->barcodenum . ".png");
             unlink(JPATH_SITE . "/images/salestickets/barcode/vertical/Event_Ticket_Bar_" .  $items->barcodenum . ".png");
        }
        
       
       
        return true;
        
    }
    
    
    
    function deleteimagesPdf($trackingcode,$Ispdf)
    {
        
        $query= "select * from #__imixtix_salestickets WHERE trackingid='".$trackingcode."'";
        $this -> _db ->setQuery($query);
        $this -> _db -> Query($query);
        $info= $this -> _db -> loadObjectlist();
        
        
        foreach($info as $keys=>$items)
        {
            
             unlink(JPATH_SITE . "/images/salestickets/barcode/qrcode/" . $items->eventid . "_" . $items->barcodenum.".png");
             unlink(JPATH_SITE . "/images/salestickets/barcode/Event_Ticket_Bar_" .  $items->barcodenum . ".png");
             unlink(JPATH_SITE . "/images/salestickets/barcode/vertical/Event_Ticket_Bar_" .  $items->barcodenum . ".png");
        }
        
       
        unlink($Ispdf);
        return true;
        
    }




    function deletecomplimentryfiles($ordernum,$Ispdf)
    {
        
        $query= "select * from #__imixtix_comptickets` WHERE ordernum='".$ordernum."'";
        $this -> _db ->setQuery($query);
        $this -> _db -> Query($query);
        $info= $this -> _db -> loadObjectlist();
        
        
        foreach($info as $keys=>$items)
        {
            
             unlink(JPATH_SITE . "/images/comptickets/barcode/qrcode/" . $items->eventid . "_" . $items->barcodenum.".png");
             unlink(JPATH_SITE . "/images/comptickets/barcode/Event_Ticket_Bar_" .  $items->barcodenum . ".png");
             unlink(JPATH_SITE . "/images/comptickets/barcode/vertical/Event_Ticket_Bar_" .  $items->barcodenum . ".png");
        }
        
       
        unlink($Ispdf);
        return true;
        
    }
    
    
    
    
    
    
    
    
    
    
    
	/**
	 * Method to get a records.
	 * @param	integer	The id of the primary key.
	 * @return	mixed	Object on success, false on failure.
	 */
	public function veiwTickets($eid) {

		if (!empty($eid)) {
			$query = $this -> _db -> getQuery(true);
			// Select the required fields from the table.
			$query -> select($this -> getState('list.select', 'a.*'));
			$query -> from('`#__imixtix_comptickets` AS a');	
			// Join over the tickets.
			$query -> select('b.tic_name');
			$query -> join('LEFT', '#__imixtix_tickets AS b ON b.id = a.ticketid');
			$query -> where('a.eventid = ' . (int)$eid);
			$this -> _db -> setQuery($query);
			return $this -> _db -> loadObjectList();
		}
		return false;
	}
	
	/**
	* Items total
	* @var integer
	*/
	var $_total = null;
	
	/**
	* Pagination object
	* @var object
	*/
	var $_pagination = null;
	
	function fetchSalesHistory($id, $data = null) {
	
		// if data hasn't already been obtained, load it
		if (empty($this->_data)) {
			$query = $this->salesHistory($id, $data);
			$this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));    
		}
		return $this->_data;
	}	

	function fetchTotal($id, $data = null) {
		
		// Load the content if it doesn't already exist
		if (empty($this->_total)) {
			$query = $this->salesHistory($id, $data);
			$this->_total = $this->_getListCount($query);       
		}
		return $this->_total;
	}
	
	function fetchPagination($id, $data = null) {
		
		// Load the content if it doesn't already exist
		if (empty($this->_pagination)) {
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination($this->fetchTotal($id, $data), $this->getState('limitstart'), $this->getState('limit') );
		}
		return $this->_pagination;
	}
		
	/**
	 * Method to get a single record.
	 * @param	integer	The id of the primary key.
	 * @return	mixed	Object on success, false on failure.
	 */
	public function salesHistory($id, $data = null) {
		
		$query = $this -> _db -> getQuery(true);
		// Select the required fields from the table.
		$query -> select($this -> getState('list.select', 'a.*'));
		$query -> from('`#__imixtix_salestickets` AS a');
		// Join over the orders
		$query -> select('b.inv_number, b.mc_gross, b.mc_fee, b.quantity, b.discount');
		$query -> join('LEFT', '#__imixtix_orders AS b ON b.trackingid = a.trackingid');
		// Join over the tickets
		$query -> select('d.total_price');
		$query -> join('LEFT', '#__imixtix_tickets AS d ON d.id = a.ticid');

		// Join over the events
		$query -> select('c.event_title');
		$query -> join('LEFT', '#__imixtix_events AS c ON c.id = a.eventid');
		$query -> where('a.eventid = ' . $id . ' and a.payment_status=1  GROUP BY b.trackingid');
		// Filter by From date
		if (!empty ($data ['fromdate'])) {
			$query -> where('(' . 'a.created_on >= "' . $data ['fromdate'] . '")');
		}
		// Filter by To date
		if (!empty ($data ['todate'])) {
			$query -> where('(' . 'a.created_on <= "' . $data ['todate'] . '")');
		}
		// Filter by Order Status
		if (!empty ($data ['buyername'])) {
			$query -> where('(' . 'a.first_name LIKE "%' . $data ['buyername'] . '%" OR a.last_name LIKE "%' . $data ['buyername'] . '%")');
		}
		// Filter by Tickets
		if (!empty ($data ['ticketid'])) {
			$tids = implode(',', $data ['ticketid']);
			$query -> where('(' . 'a.ticid IN (' . $tids . '))');
		}
		$query->order('a.created_on DESC');
		//echo nl2br(str_replace('#__','#__',$query));
		return $query;
	}	

	/**
	 * Method to retrive record(s) of Tickets table.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	function salesHistoryTickets($eventid) {

	$sql = "SELECT * FROM #__imixtix_tickets WHERE event_id = " . $eventid . " ORDER BY id";
	$this -> _db -> setQuery($sql);
	        if (!$this -> _db -> query()) {
			JError::raiseError(500, $this -> _db -> getErrorMsg());
		}
	return $this -> _db -> loadObjectList();
	}
        
        
        public function salesHistorytoCSV($id, $data = null) {
		
		$query = $this -> _db -> getQuery(true);
		// Select the required fields from the table.
		$query -> select($this -> getState('list.select', 'a.*'));
		$query -> from('`#__imixtix_salestickets` AS a');
		// Join over the orders
		$query -> select('b.inv_number, b.mc_gross, b.mc_fee, b.quantity, b.discount');
		$query -> join('LEFT', '#__imixtix_orders AS b ON b.trackingid = a.trackingid');
		
                // Join over the tickets
		$query -> select('d.total_price');
		$query -> join('LEFT', '#__imixtix_tickets AS d ON d.id = a.ticid');

                
                
		// Join over the events
		$query -> select('c.event_title');
		$query -> join('LEFT', '#__imixtix_events AS c ON c.id = a.eventid');
		
                
                
                $query -> where('a.eventid = ' . $id );
		
                //$query -> where('a.eventid = ' . $id . ' GROUP BY b.trackingid');
	
                

                // Filter by From date
		if (!empty ($data ['fromdate'])) {
			$query -> where('(' . 'a.created_on >= "' . $data ['fromdate'] . '")');
		}
                
                
		// Filter by To date
		if (!empty ($data ['todate'])) {
			$query -> where('(' . 'a.created_on <= "' . $data ['todate'] . '")');
		}
		

                // Filter by Order Status
		if (!empty ($data ['buyername'])) {
			$query -> where('(' . 'a.first_name LIKE "%' . $data ['buyername'] . '%" OR a.last_name LIKE "%' . $data ['buyername'] . '%")');
		}
		

                // Filter by Tickets
		if (!empty ($data ['ticketid'])) {
			$tids = implode(',', $data ['ticketid']);
			$query -> where('(' . 'a.ticid IN (' . $tids . '))');
		}
		
                
                $query->group('b.trackingid ');
                $query->order('a.created_on DESC');
		//echo nl2br(str_replace('#__','#__',$query));
		//return $query;
                
                $this -> _db -> setQuery($query);
                
                if($this -> _db -> loadObjectList()){
                return $this -> _db -> loadObjectList();
                }
                else
                {
                    return 0;
                }
	}	

        


	/**
	 * Method to retrive record(s) of Tickets table.
	 * @param	$_POST Array Values
	 * @return	true if success, false on failure
	 */
	function exportToCSV() {
        jimport( 'joomla.database.databasequery' );
	 $app = JFactory::getApplication();

        $eventid = JRequest::getVar ('eventid');	
		$post = JRequest::get('post');
	 	$results = $this -> salesHistorytoCSV($eventid, $post);		
	
                if(empty($results))
                {
                $msg = JText::_('No record!');
		$link='index.php?option=com_imixtix&task=events.salesHistory&eventid='.$eventid;
                $app->redirect($link, $msg);
                }
                else
                {
                    $tab = ",";
                    $data = '';
                    $data = JText::_('COM_IMIXTIX_SNO') . $tab . JText::_('COM_IMIXTIX_SALES_BUYER') . $tab . JText::_('COM_IMIXTIX_EMAIL') . $tab . JText::_('COM_IMIXTIX_SALES_ORDER_NO') . $tab . JText::_('COM_IMIXTIX_TICKET_NAME') . $tab . JText::_('COM_IMIXTIX_SALES_QTY') . $tab . JText::_('COM_IMIXTIX_SALES_GROSS') . $tab . JText::_('COM_IMIXTIX_SALES_FEE') .  $tab . JText::_('COM_IMIXTIX_SALES_DISCOUNT') .  $tab . JText::_('COM_IMIXTIX_SALES_NET') .  $tab . JText::_('COM_IMIXTIX_SALES_BARCODE') .  $tab . JText::_('COM_IMIXTIX_SALES_STATUS') . "\n";
                    for ($i = 0; $i < count($results); $i++) {

                            $data .= ($i + 1) . ',' . $results[$i] -> buyername . ',' . $results[$i] -> buyeremail . ',' . $results[$i] -> trackingid . ',' . $results[$i] -> ticket_name . ',' . $results[$i] -> quantity . ',' . $results[$i] -> mc_gross . ',' . $results[$i] -> mc_fee . ',' . $results[$i] -> discount .  ',' . (($results[$i] -> mc_gross)-($results[$i] -> mc_fee)) . ',' . $results[$i] -> barcode_image . ',' . $results[$i] -> status . "\n";

                    }
                    header("Content-type: application/octet-stream");
                    header("Content-Disposition: attachment; filename=\"SalesHistory_" . date('d_m_Y') . ".csv\"");
                    echo $data;
                    exit ;
                }
	}


        
             
        
       //@delete images attachments        
      function delete_files($event_id,$image_type_field,$type) {
     
      $db =& JFactory::getDBO();
      $Query="Update #__imixtix_events set $type=''  where ".$type." like  '".$image_type_field."%'  and id='".$event_id."'";
      $db->setQuery($Query);    
         if($db->Query())
            { 
            $path = JPATH_SITE . DS . "images" . DS . "imixtix_events" . DS;
            unlink($path.$image_type_field);
            return 1;
         }
         else
         { return 0;}
    }        
        
        //Export to central database
      public function exportEvents($data) {
	//print_r($data);die;
	foreach($data as $dat){
		$query = $this -> _db -> getQuery(true);
		// Select the required fields from the table.
		$query -> select('a.*,b.*,a.id as event_id,b.id as ticket_id'); // * b.quantity
		$query -> from('`#__imixtix_events` AS a');
		// Join over the orders
		$query -> join('LEFT', '`#__imixtix_tickets` AS b ON b.event_id = a.id');
		$query -> where('a.id = ' . $dat);
		//$query -> select('a.*'); // * b.quantity
		//$query -> from('`#__imixtix_salesticket` AS a');
		$this -> _db -> setQuery($query);
		$all[]=$this -> _db -> loadObject();
		
		//if(!empty($all->tic_name))
		//{
		//$all[]=$all;
		//}
		}
		//echo "<pre>";
		//print_r($all);
return $all;
	}  
     /**
	 * Method to AddApi and edit APIkey for export to central databse
	 * @param	$key Array Values
	 * @return	true if success, false on failure
	 */
	function addApiKey($key) {
	
	$akey=$this->getApiKey();//check if api key already added
	$k=base64_encode($key);
	
	if(empty($akey)){
	$sql = "insert into   #__get_api (id,appkey) values ('0','".$k."')";}
	else{
	$sql = "Update #__get_api set appkey='".$k."'  where id='0'";
	}
	$this -> _db -> setQuery($sql);
	        if (!$this -> _db -> query()) {
			JError::raiseError(500, $this -> _db -> getErrorMsg());
		}
	return true;
	}
       /**
	 * Method to fetch Apikey added by admin
	 * 
	 * @return	value if success, false on failure
	 */
	function getApiKey() {
	
	$sql = "Select appkey from   #__get_api limit 1";
	$this -> _db -> setQuery($sql);
	        if (!$this -> _db -> query()) {
			JError::raiseError(500, $this -> _db -> getErrorMsg());
			
		}
	$a=$this -> _db -> loadObjectList();
	if(!empty($a)){
	$k=base64_decode($a[0]->appkey);
	return $k;}
	else{ return false;}
	}     
}

<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the exhibition to.
// name:				The name of the exhibition.  
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_exhibitions_exhibitionAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'name'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Name'),
		'type'=>array('required'=>'no', 'default'=>'1', 'trimblanks'=>'yes', 'blank'=>'yes', 
			'validlist'=>array('1'), 'name'=>'Exhibition Type'),
		'tagline'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Tagline'),
		'permalink'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Permalink'),
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'End Date'), 
		// Details
		'use-exhibitors'=>array('required'=>'no', 'default'=>'yes', 'blank'=>'no', 'name'=>'Exhibitors'),
		'use-sponsors'=>array('required'=>'no', 'default'=>'yes', 'blank'=>'no', 'name'=>'Sponsors'),
		'exhibitor-label-singular'=>array('required'=>'no', 'default'=>'Exhibitor', 'blank'=>'yes', 'name'=>'Exhibitor Label'),
		'exhibitor-label-plural'=>array('required'=>'no', 'default'=>'Exhibitors', 'blank'=>'yes', 'name'=>'Exhibitor Label Plural'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	if( !isset($args['permalink']) || $args['permalink'] == '' ) {
		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($args['name'])));
	}

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'exhibitions', 'private', 'checkAccess');
    $rc = ciniki_exhibitions_checkAccess($ciniki, $args['business_id'], 'ciniki.exhibitions.exhibitionAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink FROM ciniki_exhibitions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.exhibitions', 'exhibition');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'111', 'msg'=>'You already have an exhibition with this name, please choose another name.'));
	}


	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.exhibitions');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.exhibitions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args['uuid'] = $rc['uuid'];

	//
	// Add the exhibition to the database
	//
	$strsql = "INSERT INTO ciniki_exhibitions (uuid, business_id, "
		. "name, permalink, type, description, tagline, start_date, end_date, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['description']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['tagline']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.exhibitions');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.exhibitions');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.exhibitions');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'124', 'msg'=>'Unable to add exhibition'));
	}
	$exhibition_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'uuid',
		'name',
		'permalink',
		'type',
		'description',
		'tagline',
		'start_date',
		'end_date',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.exhibitions', 
				'ciniki_exhibition_history', $args['business_id'], 
				1, 'ciniki_exhibitions', $exhibition_id, $field, $args[$field]);
		}
	}

	//
	// Check for any details
	//
	$detail_keys = array(
		'use-exhibitors',
		'use-sponsors',
		'exhibitor-label-singular',
		'exhibitor-label-plural',
		);
	foreach($detail_keys as $key_name) {
		if( isset($args[$key_name]) ) {
			$strsql = "INSERT INTO ciniki_exhibition_details (business_id, exhibition_id, "
				. "detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $exhibition_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $key_name) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $args[$key_name]) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP()) ";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.exhibitions');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.exhibitions');
				return $rc;
			}
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.exhibitions', 
				'ciniki_exhibition_history', $args['business_id'], 
				1, 'ciniki_exhibition_details', $exhibition_id, $key_name, $args[$key_name]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.exhibitions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'exhibitions');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.exhibitions.exhibition', 
		'args'=>array('id'=>$exhibition_id));

	return array('stat'=>'ok', 'id'=>$exhibition_id);
}
?>
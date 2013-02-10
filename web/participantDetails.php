<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_exhibitions_web_participantDetails($ciniki, $settings, $business_id, $exhibition_id, $permalink) {

	$strsql = "SELECT ciniki_exhibition_participants.id, "
		. "CONCAT_WS(' ', ciniki_exhibition_contacts.first, ciniki_exhibition_contacts.last) AS contact, "
		. "ciniki_exhibition_contacts.company, "
		. "ciniki_exhibition_contacts.url, "
		. "ciniki_exhibition_contacts.description, "
		. "ciniki_exhibition_contacts.primary_image_id, "
		. "ciniki_exhibition_contact_images.image_id, "
		. "ciniki_exhibition_contact_images.name AS image_name, "
		. "ciniki_exhibition_contact_images.description AS image_description, "
		. "ciniki_exhibition_contact_images.url AS image_url "
		. "FROM ciniki_exhibition_contacts "
		. "LEFT JOIN ciniki_exhibition_participants ON ("
			. "ciniki_exhibition_contacts.id = ciniki_exhibition_participants.contact_id "
			. "AND ciniki_exhibition_participants.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_exhibition_participants.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $exhibition_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_exhibition_contact_images ON ("
			. "ciniki_exhibition_contacts.id = ciniki_exhibition_contact_images.contact_id "
			. "AND (ciniki_exhibition_contact_images.webflags&0x01) = 0 "
			. ") "
		. "WHERE ciniki_exhibition_contacts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_exhibition_contacts.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		// Check the participant is visible on the website
		. "AND (ciniki_exhibition_participants.webflags&0x01) = 0 "
		// Check the participant is an exhibitor and accepted, or a sponsor
		. "AND ("
			. "((type&0x10) = 0x10 AND status = 10) "
			. "OR ((type&0x20) = 0x20) "
			. ") "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.exhibitions', array(
		array('container'=>'participants', 'fname'=>'id', 'name'=>'participant',
			'fields'=>array('id', 'contact', 'company', 'image_id'=>'primary_image_id', 
				'url', 'description')),
		array('container'=>'images', 'fname'=>'image_id', 'name'=>'image',
			'fields'=>array('image_id', 'title'=>'image_name', 
				'description'=>'image_description', 'url'=>'image_url')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['participants']) || !isset($rc['participants'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'278', 'msg'=>'Unable to find participant'));
	}
	$participant = $rc['participants'][0]['participant'];

	if( isset($participant['company']) && $participant['company'] != '' ) {
		$participant['name'] = $participant['company'];
	} else {
		$participant['name'] = $participant['contact'];
	}

	return array('stat'=>'ok', 'participant'=>$participant);
}
?>

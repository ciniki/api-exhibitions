<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the exhibition to.
// contact_id:          The ID of the contact to get.
// exhibition_id:       The ID of the exhibition to get the participant from.
// images:              Specify if the method should return the image thumbnails.
//
// Returns
// -------
//
function ciniki_exhibitions_contactGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'contact_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Participant'),
        'exhibition_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Exhibition'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'exhibitions', 'private', 'checkAccess');
    $rc = ciniki_exhibitions_checkAccess($ciniki, $args['tnid'], 'ciniki.exhibitions.contactGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT "
        . "ciniki_exhibition_contacts.id AS contact_id, "
        . "ciniki_exhibition_contacts.first, "
        . "ciniki_exhibition_contacts.last, "
        . "ciniki_exhibition_contacts.company, "
        . "ciniki_exhibition_contacts.email, "
        . "ciniki_exhibition_contacts.phone_home, "
        . "ciniki_exhibition_contacts.phone_work, "
        . "ciniki_exhibition_contacts.phone_cell, "
        . "ciniki_exhibition_contacts.phone_fax, "
        . "ciniki_exhibition_contacts.url, "
        . "ciniki_exhibition_contacts.studio_name, "
        . "ciniki_exhibition_contacts.address1, "
        . "ciniki_exhibition_contacts.address2, "
        . "ciniki_exhibition_contacts.city, "
        . "ciniki_exhibition_contacts.province, "
        . "ciniki_exhibition_contacts.postal, "
        . "ciniki_exhibition_contacts.latitude, "
        . "ciniki_exhibition_contacts.longitude, "
        . "ciniki_exhibition_contacts.mailing_address1, "
        . "ciniki_exhibition_contacts.mailing_address2, "
        . "ciniki_exhibition_contacts.mailing_city, "
        . "ciniki_exhibition_contacts.mailing_province, "
        . "ciniki_exhibition_contacts.mailing_postal, "
        . "ciniki_exhibition_contacts.primary_image_id, "
        . "ciniki_exhibition_contacts.short_description, "
        . "ciniki_exhibition_contacts.description, "
        . "ciniki_exhibition_contacts.notes, "
        . "IFNULL(ciniki_exhibition_participants.id, 0) AS participant_id, "
        . "IFNULL(ciniki_exhibition_participants.exhibition_id, 0) AS exhibition_id, "
        . "IFNULL(ciniki_exhibition_participants.category, '') AS category, "
        . "IFNULL(ciniki_exhibition_participants.type, '') AS type, "
        . "IFNULL(ciniki_exhibition_participants.status, 0) AS status, "
        . "IFNULL(ciniki_exhibition_participants.webflags, 0) AS webflags, "
        . "IFNULL(ciniki_exhibition_participants.title, '') AS title, "
        . "IFNULL(ciniki_exhibition_participants.location, '') AS location ";
    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql .= ", "
            . "ciniki_exhibition_contact_images.id AS img_id, "
            . "ciniki_exhibition_contact_images.name AS image_name, "
            . "ciniki_exhibition_contact_images.webflags AS image_webflags, "
            . "ciniki_exhibition_contact_images.image_id, "
            . "ciniki_exhibition_contact_images.description AS image_description, "
            . "ciniki_exhibition_contact_images.url AS image_url "
            . "";
    }
    $strsql .= "FROM ciniki_exhibition_contacts "
        . "LEFT JOIN ciniki_exhibition_participants ON ("
            . "ciniki_exhibition_contacts.id = ciniki_exhibition_participants.contact_id "
            . "AND ciniki_exhibition_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            if( isset($args['exhibition_id']) && $args['exhibition_id'] > 0 ) {
                $strsql .= "AND ciniki_exhibition_participants.exhibition_id = '" . ciniki_core_dbQuote($ciniki, $args['exhibition_id']) . "' ";
            }
            $strsql .= ") ";
    $strsql .= "LEFT JOIN ciniki_exhibitions ON ("
            . "ciniki_exhibition_participants.exhibition_id = ciniki_exhibitions.id "
            . "AND ciniki_exhibitions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql .= "LEFT JOIN ciniki_exhibition_contact_images ON (ciniki_exhibition_contacts.id = ciniki_exhibition_contact_images.contact_id "
            . "AND ciniki_exhibition_contact_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $strsql .= "WHERE ciniki_exhibition_contacts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_exhibition_contacts.id = '" . ciniki_core_dbQuote($ciniki, $args['contact_id']) . "' "
        . "ORDER BY ciniki_exhibition_contacts.id, ciniki_exhibitions.start_date ASC ";

    //
    // Check if we need to include thumbnail images
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.exhibitions', array(
            array('container'=>'contacts', 'fname'=>'contact_id', 'name'=>'contact',
                'fields'=>array('participant_id', 'contact_id', 'category', 'type', 'status',
                    'webflags', 'title', 'location', 
                    'contact_id', 'first', 'last', 'company', 'email', 'phone_home',
                    'phone_work', 'phone_cell', 'phone_fax', 'url', 'studio_name',
                    'address1', 'address2', 'city', 'province', 'postal', 'latitude', 'longitude',
                    'mailing_address1', 'mailing_address2', 'mailing_city', 'mailing_province', 
                    'mailing_postal', 
                    'primary_image_id', 'short_description', 'description', 'notes')),
            array('container'=>'images', 'fname'=>'img_id', 'name'=>'image',
                'fields'=>array('id'=>'img_id', 'name'=>'image_name', 'webflags'=>'image_webflags',
                    'image_id', 'description'=>'image_description', 'url'=>'image_url')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['contacts']) || !isset($rc['contacts'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.exhibitions.5', 'msg'=>'Unable to find participant'));
        }
        $contact = $rc['contacts'][0]['contact'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
        foreach($participant['images'] as $img_id => $img) {
            if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image']['image_id'], 75);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $participant['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
            }
        }
    } else {
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.exhibitions', array(
            array('container'=>'contacts', 'fname'=>'contact_id', 'name'=>'contact',
                'fields'=>array('participant_id', 'contact_id', 'category', 'type', 'status',
                    'webflags', 'title', 'location', 
                    'contact_id', 'first', 'last', 'company', 'email', 'phone_home',
                    'phone_work', 'phone_cell', 'phone_fax', 'url', 'studio_name',
                    'address1', 'address2', 'city', 'province', 'postal', 'latitude', 'longitude',
                    'primary_image_id', 'short_description', 'description', 'notes')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['contacts']) || !isset($rc['contacts'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.exhibitions.6', 'msg'=>'Unable to find contact'));
        }
        $contact = $rc['contacts'][0]['contact'];
    }
    
    return array('stat'=>'ok', 'contact'=>$contact);
}
?>

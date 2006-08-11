<?php
//
// Created on: <13-Feb-2005 03:13:00 bh>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.8.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

include_once( 'kernel/common/template.php' );
include_once( 'kernel/classes/ezpreferences.php' );
include_once( 'kernel/classes/ezinformationcollection.php' );

$http =& eZHTTPTool::instance();
$module =& $Params['Module'];
$offset = $Params['Offset'];

if( !is_numeric( $offset ) )
{
    $offset = 0;
}


if( $module->isCurrentAction( 'RemoveObjectCollection' ) && $http->hasPostVariable( 'ObjectIDArray' ) )
{
    $objectIDArray = $http->postVariable( 'ObjectIDArray' );
    $http->setSessionVariable( 'ObjectIDArray', $objectIDArray );

    $collections = 0;

    foreach( $objectIDArray as $objectID )
    {
        $collections += eZInformationCollection::fetchCollectionCountForObject( $objectID );
    }

    $tpl =& templateInit();
    $tpl->setVariable( 'module', $module );
    $tpl->setVariable( 'collections', $collections );
    $tpl->setVariable( 'remove_type', 'objects' );

    $Result = array();
    $Result['content'] =& $tpl->fetch( 'design:infocollector/confirmremoval.tpl' );
    $Result['path'] = array( array( 'url' => false,
                                    'text' => ezi18n( 'kernel/infocollector', 'Collected information' ) ) );
    return;
}


if( $module->isCurrentAction( 'ConfirmRemoval' ) )
{

    $objectIDArray =& $http->sessionVariable( 'ObjectIDArray' );

    if( is_array( $objectIDArray) )
    {
        foreach( $objectIDArray as $objectID )
        {
            eZInformationCollection::removeContentObject( $objectID );
        }
    }
}


if( eZPreferences::value( 'admin_infocollector_list_limit' ) )
{
    switch( eZPreferences::value( 'admin_infocollector_list_limit' ) )
    {
        case '2': { $limit = 25; } break;
        case '3': { $limit = 50; } break;
        default:  { $limit = 10; } break;
    }
}
else
{
    $limit = 10;
}

if ( isset( $Params['UserParameters'] ) )
{
    $UserParameters = $Params['UserParameters'];
}
else
{
    $UserParameters = array();
}
$viewParameters = array( 'offset' => $offset, 'filter' => false );
$viewParameters = array_merge( $viewParameters, $UserParameters );

// Create filtering by first letter
$objectNameFilterSQL = eZContentObjectTreeNode::createObjectNameFilterConditionSQLString( $viewParameters['filter'] );
$whereSQLCount = $tableSQLCount = '';
if ( $objectNameFilterSQL != '' )
{
    $whereSQLCount = "WHERE ezinfocollection.contentobject_id=ezcontentobject.id $objectNameFilterSQL";
    $tableSQLCount = ', ezcontentobject';

}
$db =& eZDB::instance();
$objects = $db->arrayQuery( 'SELECT DISTINCT ezinfocollection.contentobject_id,
                                    ezcontentobject.name,
                                    ezcontentobject_tree.main_node_id,
                                    ezcontentclass.name AS class_name,
                                    ezcontentclass.identifier AS class_identifier
                             FROM   ezinfocollection,
                                    ezcontentobject,
                                    ezcontentobject_tree,
                                    ezcontentclass
                             WHERE  ezinfocollection.contentobject_id=ezcontentobject.id
                                    AND ezcontentobject.contentclass_id=ezcontentclass.id
                                    AND ezinfocollection.contentobject_id=ezcontentobject_tree.contentobject_id ' . $objectNameFilterSQL,
                             array( 'limit'  => (int)$limit,
                                    'offset' => (int)$offset ) );

$infoCollectorObjectsQuery = $db->arrayQuery( "SELECT COUNT( DISTINCT contentobject_id ) as count FROM ezinfocollection $tableSQLCount $whereSQLCount" );
$numberOfInfoCollectorObjects = 0;

if ( $infoCollectorObjectsQuery )
{
    $numberOfInfoCollectorObjects = $infoCollectorObjectsQuery[0]['count'];
}

for( $i=0; $i<count( $objects ); $i++ )
{
    $collections = eZInformationCollection::fetchCollectionsList( (int)$objects[$i]['contentobject_id'], /* object id */
                                                                  false, /* creator id */
                                                                  false, /* user identifier */
                                                                  false, /* limitArray */
                                                                  false, /* sortArray */
                                                                  false  /* asObject */
                                                                 );

    $first = $collections[0]['created'];
    $last  = $first;

    for($j=0; $j<count( $collections ); $j++ )
    {
        $current = $collections[$j]['created'];

        if( $current < $first )
            $first = $current;

        if( $current > $last )
            $last = $current;
    }

    $objects[$i]['first_collection'] = $first;
    $objects[$i]['last_collection'] = $last;
    $objects[$i]['collections']= count( $collections );
}

$tpl =& templateInit();
$tpl->setVariable( 'module', $module );
$tpl->setVariable( 'limit', $limit );
$tpl->setVariable( 'view_parameters', $viewParameters );
$tpl->setVariable( 'object_array', $objects );
$tpl->setVariable( 'object_count', $numberOfInfoCollectorObjects );

$Result = array();
$Result['content'] =& $tpl->fetch( 'design:infocollector/overview.tpl' );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'kernel/infocollector', 'Collected information' ) ) );

?>

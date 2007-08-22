<?php
//
// Created on: <27-Aug-2002 15:42:43 bf>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.9.x
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

include_once( 'kernel/classes/ezsection.php' );
include_once( 'kernel/common/template.php' );
include_once( 'kernel/classes/ezpreferences.php' );

$http =& eZHTTPTool::instance();
$Module =& $Params["Module"];
$tpl =& templateInit();
$tpl->setVariable( 'module', $Module );

$offset = $Params['Offset'];

if( eZPreferences::value( 'admin_section_list_limit' ) )
{
    switch( eZPreferences::value( 'admin_section_list_limit' ) )
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

if ( $http->hasPostVariable( 'CreateSectionButton' ) )
{
    $Module->redirectTo( $Module->functionURI( "edit" ) . '/0/' );
    return;
}

if ( $http->hasPostVariable( 'RemoveSectionButton' ) )
{
    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
    $currentUser =& eZUser::currentUser();
    $accessResult = $currentUser->hasAccessTo( 'section', 'edit' );
    if ( $accessResult['accessWord'] == 'yes' )
    {
        $sectionIDArray = $http->postVariable( 'SectionIDArray' );
        $http->setSessionVariable( 'SectionIDArray', $sectionIDArray );
        $sections = array();
        foreach ( $sectionIDArray as $sectionID )
        {
            $section = eZSection::fetch( $sectionID );
            $sections[] =& $section;
        }
        $tpl->setVariable( 'delete_result', $sections );
        $Result = array();
        $Result['content'] =& $tpl->fetch( "design:section/confirmremove.tpl" );
        $Result['path'] = array( array( 'url' => false,
                                        'text' => ezi18n( 'kernel/section', 'Sections' ) ) );
        return;
    }
    else
    {
        return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );
    }
}

if ( $http->hasPostVariable( 'ConfirmRemoveSectionButton' ) )
{
    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
    $currentUser =& eZUser::currentUser();
    $accessResult = $currentUser->hasAccessTo( 'section', 'edit' );
    if ( $accessResult['accessWord'] == 'yes' )
    {
        $sectionIDArray =& $http->sessionVariable( 'SectionIDArray' );

        $db =& eZDB::instance();
        $db->begin();
        include_once( 'kernel/classes/ezcontentcachemanager.php' );
        foreach ( $sectionIDArray as $sectionID )
        {
            $section = eZSection::fetch( $sectionID );
            if( $section === null )
                continue;
            // Clear content cache if needed
            eZContentCacheManager::clearContentCacheIfNeededBySectionID( $sectionID );
            $section->remove( );
        }
        $db->commit();
    }
    else
    {
        return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );
    }
}

$viewParameters = array( 'offset' => $offset );
$sectionArray = eZSection::fetchByOffset( $offset, $limit );
$sectionCount = eZSection::sectionCount();

$tpl->setVariable( "limit", $limit );
$tpl->setVariable( 'section_array', $sectionArray );
$tpl->setVariable( 'section_count', $sectionCount );
$tpl->setVariable( 'view_parameters', $viewParameters );

$Result = array();
$Result['content'] =& $tpl->fetch( "design:section/list.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'kernel/section', 'Sections' ) ) );

?>

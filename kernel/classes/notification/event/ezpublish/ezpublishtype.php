<?php
//
// Definition of eZPublishType class
//
// Created on: <12-May-2003 13:29:25 sp>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.10.x
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

/*! \file ezpublishtype.php
*/

/*!
  \class eZPublishType ezpublishtype.php
  \brief The class eZPublishType does

*/
define( 'EZ_NOTIFICATIONTYPESTRING_PUBLISH', 'ezpublish' );

class eZPublishType extends eZNotificationEventType
{
    /*!
     Constructor
    */
    function eZPublishType()
    {
        $this->eZNotificationEventType( EZ_NOTIFICATIONTYPESTRING_PUBLISH );
    }

    function initializeEvent( &$event, $params )
    {
        eZDebugSetting::writeDebug( 'kernel-notification', $params, 'params for type' );
        $event->setAttribute( 'data_int1', $params['object'] );
        $event->setAttribute( 'data_int2', $params['version'] );
    }

    function eventContent( &$event )
    {
        return eZContentObjectVersion::fetchVersion( $event->attribute( 'data_int2' ), $event->attribute( 'data_int1' ) );
    }
}

eZNotificationEventType::register( EZ_NOTIFICATIONTYPESTRING_PUBLISH, 'ezpublishtype' );

?>

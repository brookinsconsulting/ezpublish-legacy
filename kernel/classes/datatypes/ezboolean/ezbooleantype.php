<?php
//
// Definition of eZBooleanType class
//
// Created on: <27-Jun-2002 18:24:54 sp>
//
// Copyright (C) 1999-2003 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/home/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

//!! eZKernel
//! The class eZBooleanType does
/*!

*/

include_once( "kernel/classes/ezdatatype.php" );

define( "EZ_DATATYPESTRING_BOOLEAN", "ezboolean" );

class eZBooleanType extends eZDataType
{
    function eZBooleanType()
    {
        $this->eZDataType( EZ_DATATYPESTRING_BOOLEAN, "Checkbox" );
    }

    /*!
     Store content
    */
    function storeObjectAttribute( &$attribute )
    {
    }


   /*!
     Sets the default value.
    */
    function initializeObjectAttribute( &$contentObjectAttribute, $currentVersion, &$originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $dataInt = $originalContentObjectAttribute->attribute( "data_int" );
            $contentObjectAttribute->setAttribute( "data_int", $dataInt );
        }
        else
        {
            $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();
            $default = $contentClassAttribute->attribute( "data_int3" );
	    $contentObjectAttribute->setAttribute( "data_int", $default );
        }
    }


    /*!
     Fetches the http post var integer input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        if( $http->hasPostVariable( $base . "_data_boolean_" . $contentObjectAttribute->attribute( "id" ) ))
        {
            $data = $http->postVariable( $base . "_data_boolean_" . $contentObjectAttribute->attribute( "id" ) );
            if( isset( $data ) )
                $data = 1;
        }
        else
        {
            $data = 0;
        }
        $contentObjectAttribute->setAttribute( "data_int", $data );
    }

    function fetchClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
    
        if( $http->hasPostVariable( $base . "_ezboolean_default_value_" . $classAttribute->attribute( "id" ) ))
	{
            $data = $http->postVariable( $base . "_ezboolean_default_value_" . $classAttribute->attribute( "id" ) );
            if( isset( $data ) )
                $data = 1;
        }
        else
        {
            $data = 0;
        }
	
        $classAttribute->setAttribute( "data_int3", $data );
   
    }
   

/*!
     Returns the content.
    */
    function &objectAttributeContent( &$contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( "data_int" );
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( "data_int" );
    }

    /*!
     Returns the integer value.
    */
    function title( &$contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( "data_int" );
    }
}

eZDataType::register( EZ_DATATYPESTRING_BOOLEAN, "ezbooleantype" );

?>

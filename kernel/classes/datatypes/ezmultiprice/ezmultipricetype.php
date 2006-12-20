<?php
//
// Definition of eZMultiPriceType class
//
// Created on: <04-Nov-2005 16:54:35 dl>
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

/*!
  \class eZMultiPriceType ezmultipricetype.php
  \ingroup eZMultiDatatype
  \brief Stores a price in multicurrency.

*/

include_once( 'kernel/classes/ezdatatype.php' );
include_once( 'kernel/classes/datatypes/ezmultiprice/ezmultiprice.php' );
include_once( 'lib/ezutils/classes/ezstringutils.php' );
define( 'EZ_DATATYPESTRING_MULTIPRICE', 'ezmultiprice' );
define( 'EZ_DATATYPESTRING_DEFAULT_CURRENCY_CODE_FIELD', 'data_text1' );
define( 'EZ_DATATYPESTRING_CURRENCY_CODE_VARIABLE', '_ezmultiprice_currency_code_' );
define( 'EZ_DATATYPESTRING_MULTIPRICE_INCLUDE_VAT_FIELD', 'data_int1' );
define( 'EZ_DATATYPESTRING_MULTIPRICE_INCLUDE_VAT_VARIABLE', '_ezmultiprice_include_vat_' );
define( 'EZ_DATATYPESTRING_MULTIPRICE_VAT_ID_FIELD', 'data_float1' );
define( 'EZ_DATATYPESTRING_MULTIPRICE_VAT_ID_VARIABLE', '_ezmultiprice_vat_id_' );
define( 'EZ_MULTIPRICE_INCLUDED_VAT', 1 );
define( 'EZ_MULTIPRICE_EXCLUDED_VAT', 2 );

class eZMultiPriceType extends eZDataType
{
    function eZMultiPriceType()
    {
        $this->eZDataType( EZ_DATATYPESTRING_MULTIPRICE, ezi18n( 'kernel/classes/datatypes', 'Multi-price', 'Datatype name' ),
                            array( 'serialize_supported' => true ) );
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        // Check "price inc/ex VAT" and "VAT type" fields.
        $vatTypeID = $http->postVariable( $base . '_ezmultiprice_vat_id_' . $contentObjectAttribute->attribute( 'id' ) );
        $vatExInc = $http->postVariable( $base . '_ezmultiprice_inc_ex_vat_' . $contentObjectAttribute->attribute( 'id' ) );


        if ( $vatExInc == 1 && $vatTypeID == -1 )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Dynamic VAT cannot be included.' ) );
            return EZ_INPUT_VALIDATOR_STATE_INVALID;
        }

        // Check price.
        if ( $http->hasPostVariable( $base . '_price_array_' . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $customPriceList = $http->postVariable( $base . '_price_array_' . $contentObjectAttribute->attribute( "id" ) );
            foreach ( $customPriceList as $currencyCode => $value )
            {
                if( $contentObjectAttribute->validateIsRequired() || ( $value != '' ) )
                {
                    if ( !preg_match( "#^[0-9]+(.){0,1}[0-9]{0,2}$#", $value ) )
                    {
                        $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                             "Invalid price for '%currencyCode' currency ",
                                                                             false,
                                                                             array( '%currencyCode' => $currencyCode ) ) );
                        return EZ_INPUT_VALIDATOR_STATE_INVALID;
                    }
                }
            }
        }

        return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }

    function storeObjectAttribute( &$attribute )
    {
        $multiprice =& $attribute->attribute( 'content' );
        $multiprice->store();
    }

    /* ???????????????
    function metaData( $contentObjectAttribute )
    {
        return eZPriceType::metaData( $contentObjectAttribute );
        //return $contentObjectAttribute->attribute( "data_float" );
    }
    */

    /*!
     Set default class attribute value
    */
    function initializeClassAttribute( &$classAttribute )
    {
        if ( $classAttribute->attribute( EZ_DATATYPESTRING_MULTIPRICE_INCLUDE_VAT_FIELD ) == 0 )
            $classAttribute->setAttribute( EZ_DATATYPESTRING_MULTIPRICE_INCLUDE_VAT_FIELD, EZ_MULTIPRICE_INCLUDED_VAT );
        $classAttribute->store();
    }

    /*!
     Set default object attribute value.
    */
    function postInitializeObjectAttribute( &$objectAttribute, $currentVersion, &$originalContentObjectAttribute )
    {
        $contentClassAttribute =& $objectAttribute->contentClassAttribute();
        $multiprice = new eZMultiPrice( $contentClassAttribute, $objectAttribute );

        if ( $currentVersion == false )
        {
            $defaultCurrency = $contentClassAttribute->attribute( EZ_DATATYPESTRING_DEFAULT_CURRENCY_CODE_FIELD );
            $multiprice->setCustomPrice( $defaultCurrency, '0.00' );
            $multiprice->updateAutoPriceList();
            $multiprice->store();
        }
        else
        {
            $originalMultiprice =& $originalContentObjectAttribute->content();
            $multiprice = new eZMultiPrice( $contentClassAttribute, $objectAttribute );

            $priceList =& $originalMultiprice->priceList();
            foreach ( $priceList as $price )
                $multiprice->setPriceByCurrency( $price->attribute( 'currency_code' ), $price->attribute( 'value' ), $price->attribute( 'type') );

            $multiprice->store();
        }
    }

    function fetchClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
        $currencyCodeVariable = $base . EZ_DATATYPESTRING_CURRENCY_CODE_VARIABLE . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $currencyCodeVariable ) )
        {
            $currencyCode = $http->postVariable( $currencyCodeVariable );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_DEFAULT_CURRENCY_CODE_FIELD, $currencyCode );
        }

        $isVatIncludedVariable = $base . EZ_DATATYPESTRING_MULTIPRICE_INCLUDE_VAT_VARIABLE . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $isVatIncludedVariable ) )
        {
            $isVatIncluded = $http->postVariable( $isVatIncludedVariable );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_MULTIPRICE_INCLUDE_VAT_FIELD, $isVatIncluded );
        }
        $vatIDVariable = $base . EZ_DATATYPESTRING_MULTIPRICE_VAT_ID_VARIABLE . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $vatIDVariable  ) )
        {
            $vatID = $http->postVariable( $vatIDVariable  );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_MULTIPRICE_VAT_ID_FIELD, $vatID );
        }
        return true;
    }

    /*!
     Fetches the http post var integer input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        $multiprice =& $contentObjectAttribute->attribute( 'content' );

        $priceArrayName = $base . "_price_array_" . $contentObjectAttribute->attribute( "id" );
        if ( $http->hasPostVariable( $priceArrayName ) )
        {
            $customPriceList = $http->postVariable( $priceArrayName );

            foreach ( $customPriceList as $currencyCode => $value )
                $multiprice->setCustomPrice( $currencyCode, $value );
        }

        $multiprice->updateAutoPriceList();

        $vatType = $http->postVariable( $base . '_ezmultiprice_vat_id_' . $contentObjectAttribute->attribute( 'id' ) );
        $vatExInc = $http->postVariable( $base . '_ezmultiprice_inc_ex_vat_' . $contentObjectAttribute->attribute( 'id' ) );
        $multiprice->setAttribute( 'selected_vat_type', $vatType );
        $multiprice->setAttribute( 'is_vat_included', $vatExInc );

        $data_text = $vatType . ',' . $vatExInc;
        $contentObjectAttribute->setAttribute( 'data_text', $data_text );

        return true;
    }

    /*!
     Returns the content.
    */
    function &objectAttributeContent( &$contentObjectAttribute )
    {
        $classAttribute =& $contentObjectAttribute->contentClassAttribute();
        $multiprice = new eZMultiPrice( $classAttribute, $contentObjectAttribute );

        if ( $contentObjectAttribute->attribute( 'data_text' ) != '' )
        {
            list( $vatType, $vatExInc ) = explode( ',', $contentObjectAttribute->attribute( 'data_text' ), 2 );

            $multiprice->setAttribute( 'selected_vat_type', $vatType );
            $multiprice->setAttribute( 'is_vat_included', $vatExInc );
        }

        return $multiprice;
    }

    /*!
     Returns class content.
    */
    function &classAttributeContent( &$classAttribute )
    {
        $contentObjectAttribute = false;
        $multiprice = new eZMultiPrice( $classAttribute, $contentObjectAttribute );
        return $multiprice;
    }

    function customObjectAttributeHTTPAction( $http, $action, &$contentObjectAttribute )
    {
        switch ( $action )
        {
            case 'set_custom_price' :
            {
                $selectedCurrencyName = 'ContentObjectAttribute' . '_selected_currency_' . $contentObjectAttribute->attribute( 'id' );
                if ( $http->hasPostVariable( $selectedCurrencyName ) )
                {
                    $selectedCurrency = $http->postVariable( $selectedCurrencyName );
                    $multiprice =& $contentObjectAttribute->content();

                    // to keep right order of currency after adding we do 'remove' and 'add'
                    // instead of just '$multiprice->setCustomPrice( $currencyCode, false )'
                    $price =& $multiprice->priceByCurrency( $selectedCurrency );
                    $multiprice->removePriceByCurrency( $selectedCurrency );
                    $multiprice->setCustomPrice( $selectedCurrency, $price->attribute( 'value' ) );

                    $multiprice->store();
                }
            }break;

            case 'remove_prices' :
            {
                $removePriceArrayName = 'ContentObjectAttribute' . '_remove_price_array_' . $contentObjectAttribute->attribute( 'id' );
                if ( $http->hasPostVariable( $removePriceArrayName ) )
                {
                    $removePriceArray = $http->postVariable( $removePriceArrayName );
                    $multiprice =& $contentObjectAttribute->content();

                    foreach( $removePriceArray as $currencyCode => $value )
                        $multiprice->setAutoPrice( $currencyCode, false );

                    $multiprice->updateAutoPriceList();
                    $multiprice->store();
                }
            }break;

            default :
            {
                eZDebug::writeError( 'Unknown custom HTTP action: ' . $action, 'eZMultiPriceType' );
            }break;
        }
    }

    function contentActionList( )
    {
        return array( array( 'name' => ezi18n( 'kernel/classes/datatypes', 'Add to basket' ),
                             'action' => 'ActionAddToBasket'
                             ),
                      array( 'name' => ezi18n( 'kernel/classes/datatypes', 'Add to wish list' ),
                             'action' => 'ActionAddToWishList'
                             ) );
    }

    /*!
     Clean up stored object attribute
    */
    function deleteStoredObjectAttribute( &$objectAttribute, $version = null )
    {
        $multiprice =& $objectAttribute->content();
        $multiprice->remove( $objectAttribute->attribute( 'id' ), $version );
    }

    function title( &$contentObjectAttribute )
    {
        return '';
    }

    function hasObjectAttributeContent( &$contentObjectAttribute )
    {
        return true;
    }

    function toString( $contentObjectAttribute )
    {

        $multiprice = $contentObjectAttribute->attribute( 'content' );

        $priceList = $multiprice->attribute( 'price_list' );

        $priceArray = explode( ',', $contentObjectAttribute->attribute( 'data_text' ) );
        foreach ( $priceList as $priceData )
        {
            $type = $priceData->attribute( 'type' );
            if ( $type == 1 )
            {
                $type = 'CUSTOM';
            }
            else if ( $type == 2 )
            {
                $type = 'AUTO';
            }
            else
                $type = 'LIMIT';
            $priceArray = array_merge(  $priceArray, array( $priceData->attribute( 'currency_code'), $priceData->attribute( 'value' ), $type ) );
        }
        return eZStringUtils::implodeStr( $priceArray, '|' );
    }


    function fromString( &$contentObjectAttribute, $string )
    {
        if ( $string == '' )
            return true;

        $multiprice = $contentObjectAttribute->attribute( 'content' );

        $multipriceData =  eZStringUtils::explodeSTR( $string, '|' );

        $vatType = array_shift( $multipriceData );
        $vatExInc = array_shift( $multipriceData );

        $contentObjectAttribute->setAttribute( 'data_text', $vatType . ',' . $vatExInc );

        while ( $multipriceData )
        {
            $currencyCode = array_shift( $multipriceData );
            $value = array_shift( $multipriceData );

            $type = array_shift( $multipriceData );
            if ( $type == 'CUSTOM' )
            {
                $type = 1;
            }
            else if ( $type == 'AUTO' )
            {
                $type = 2;
            }
            else
                $type = 5000;

            $multiprice->setPriceByCurrency( $currencyCode, $value, $type );

        }
        $multiprice->store();
        return $multiprice;

    }

    /*!
     \reimp
    */
    function serializeContentClassAttribute( &$classAttribute, &$attributeNode, &$attributeParametersNode )
    {
        $price =& $classAttribute->content();
        if ( $price )
        {
            $vatIncluded = $price->attribute( 'is_vat_included' );
            $vatTypes = $price->attribute( 'vat_type' );
            $attributeParametersNode->appendChild( eZDOMDocument::createElementNode( 'vat-included',
                                                                                     array( 'is-set' => $vatIncluded ? 'true' : 'false' ) ) );
            $vatTypeNode = eZDOMDocument::createElementNode( 'vat-type' );
            $chosenVatType = $classAttribute->attribute( EZ_DATATYPESTRING_MULTIPRICE_VAT_ID_FIELD );
            $gotVat = false;
            foreach ( $vatTypes as $vatType )
            {
                $id = $vatType->attribute( 'id' );
                if ( $id == $chosenVatType )
                {
                    $vatTypeNode->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $vatType->attribute( 'name' ) ) );
                    $vatTypeNode->appendAttribute( eZDOMDocument::createAttributeNode( 'percentage', $vatType->attribute( 'percentage' ) ) );
                    $gotVat = true;
                    break;
                }
            }
            if ( $gotVat )
                $attributeParametersNode->appendChild( $vatTypeNode );

            $defualtCurrency = $classAttribute->attribute( EZ_DATATYPESTRING_DEFAULT_CURRENCY_CODE_FIELD );
            $attributeParametersNode->appendChild( eZDOMDocument::createElementNode( 'default-currency', array( 'code' => $defualtCurrency ) ) );
        }
    }

    /*!
     \reimp
    */
    function unserializeContentClassAttribute( &$classAttribute, &$attributeNode, &$attributeParametersNode )
    {
        $vatNode =& $attributeParametersNode->elementByName( 'vat-included' );
        $vatIncluded = strtolower( $vatNode->attributeValue( 'is-set' ) ) == 'true';
        if ( $vatIncluded )
            $vatIncluded = EZ_MULTIPRICE_INCLUDED_VAT;
        else
            $vatIncluded = EZ_MULTIPRICE_EXCLUDED_VAT;

        $classAttribute->setAttribute( EZ_DATATYPESTRING_MULTIPRICE_INCLUDE_VAT_FIELD, $vatIncluded );
        $vatTypeNode =& $attributeParametersNode->elementByName( 'vat-type' );
        $vatName = $vatTypeNode->attributeValue( 'name' );
        $vatPercentage = $vatTypeNode->attributeValue( 'percentage' );
        $vatID = false;
        $vatTypes = eZVATType::fetchList();
        foreach ( array_keys( $vatTypes ) as $vatTypeKey )
        {
            $vatType =& $vatTypes[$vatTypeKey];
            if ( $vatType->attribute( 'name' ) == $vatName and
                 $vatType->attribute( 'percentage' ) == $vatPercentage )
            {
                $vatID = $vatType->attribute( 'id' );
                break;
            }
        }
        if ( !$vatID )
        {
            $vatType = eZVATType::create();
            $vatType->setAttribute( 'name', $vatName );
            $vatType->setAttribute( 'percentage', $vatPercentage );
            $vatType->store();
            $vatID = $vatType->attribute( 'id' );
        }
        $classAttribute->setAttribute( EZ_DATATYPESTRING_MULTIPRICE_VAT_ID_FIELD, $vatID );

        $defaultCurrency =& $attributeParametersNode->elementByName( 'default-currency' );
        $currencyCode = $defaultCurrency->attributeValue( 'code' );
        $classAttribute->setAttribute( EZ_DATATYPESTRING_DEFAULT_CURRENCY_CODE_FIELD, $currencyCode );
    }


    /*!
     \reimp
    */
    function serializeContentObjectAttribute( &$package, &$objectAttribute )
    {
        $node = $this->createContentObjectAttributeDOMNode( $objectAttribute );

        $multiprice =& $objectAttribute->content();
        $domDocument = $multiprice->DOMDocument();

        $node->appendChild( $domDocument->root() );

        return $node;
    }

    /*!
     \reimp
    */
    function unserializeContentObjectAttribute( &$package, &$objectAttribute, $attributeNode )
    {
        $rootNode = $attributeNode->firstChild();

        $multiprice =& $objectAttribute->content();
        $multiprice->decodeDOMTree( $rootNode );
    }

    function customSorting()
    {
        return true;
    }

    function customSortingSQL( $params )
    {
        $multipriceTableAlias = "mp";

        if ( isset( $params['table_alias_suffix'] ) )
            $multipriceTableAlias .= $params['table_alias_suffix'];

        $sql = array( 'from' => '',
                      'where' => '',
                      'sorting_field' => '' );

        $sql['from'] =  "ezmultipricedata $multipriceTableAlias";

        $and = '';
        if ( isset( $params['contentobject_attr_id'] ) )
        {
            $sql['where'] = "
                     $multipriceTableAlias.contentobject_attr_id = {$params['contentobject_attr_id']}";
            $and = ' AND';
        }

        if ( isset( $params['contentobject_attr_version'] ) )
        {
            $sql['where'] .= "
                    $and $multipriceTableAlias.contentobject_attr_version = {$params['contentobject_attr_version']}";
            $and = ' AND';
        }

        if ( !isset( $params['currency_code'] ) )
        {
            include_once( 'kernel/shop/classes/ezshopfunctions.php' );
            $params['currency_code'] = eZShopFunctions::preferredCurrencyCode();
        }

        if ( $params['currency_code'] !== false )
        {
            $sql['where'] .= "
                    $and $multipriceTableAlias.currency_code = '{$params['currency_code']}'";
            $and = ' AND';
        }

        $sql['sorting_field'] = "$multipriceTableAlias.value";

        return $sql;
    }

    /*!
      \reimp
    */
    function diff( $old, $new, $options = false )
    {
        return null;
    }
}

eZDataType::register( EZ_DATATYPESTRING_MULTIPRICE, "ezmultipricetype" );

?>

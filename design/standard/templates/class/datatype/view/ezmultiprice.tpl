{* DO NOT EDIT THIS FILE! Use an override template instead. *}
<div class="block">
    <label>{'Default currency'|i18n( 'design/standard/class/datatype' )}:</label>
    {$class_attribute.data_text1}
</div>

<div class="block">
    <label>{'VAT'|i18n( 'design/standard/class/datatype' )}:</label>
    {section show=eq( $class_attribute.data_int1, 1 )}{'Price inc. VAT'|i18n( 'design/standard/class/datatype' )}{/section}
    {section show=eq( $class_attribute.data_int1, 2 )}{'Price ex. VAT'|i18n( 'design/standard/class/datatype' )}{/section}
</div>

<div class="block">
<label>{'VAT type'|i18n( 'design/standard/class/datatype' )}:</label>
{section name=VatTypeList loop=$class_attribute.content.vat_type}
{switch match=$VatTypeList:item.id}
{case match=$class_attribute.data_float1}
    {$VatTypeList:item.name|wash}, {$VatTypeList:item.percentage}%
{/case}
{case/}
{/switch}
{/section}
</div>

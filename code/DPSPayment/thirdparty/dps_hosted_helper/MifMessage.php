<?php
#******************************************************************************
#* Name          : PxPay.inc
#* Description   : The objects for PX Payment page  
#* Copyright	 : Direct Payment Solutions 2007(c)
#* Date          : 2006-03-24
#* Modifications : 
#* Version	 : 1.0
#******************************************************************************

# MifMessage.
# Use this class to parse a DPS PX MifMessage in XML form,
# and access the content.
class MifMessage
{
    public $xml_;
    public $xml_index_;
    public $xml_value_;

  # Constructor:
  # Create a MifMessage with the specified XML text.
  # The constructor returns a null object if there is a parsing error.
  public function MifMessage($xml)
  {
      $p = xml_parser_create();
      xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
      $ok = xml_parse_into_struct($p, $xml, $value, $index);
      xml_parser_free($p);
      if ($ok) {
          $this->xml_ = $xml;
          $this->xml_value_ = $value;
          $this->xml_index_ = $index;
      }
    #print_r($this->xml_value_); # JH_DEBUG
  }

  # Return the value of the specified top-level attribute.
  # This method can only return attributes of the root element.
  # If the attribute is not found, return "".
  public function get_attribute($attribute)
  {
      #$attribute = strtoupper($attribute);
    $attributes = $this->xml_value_[0]["attributes"];
      return $attributes[$attribute];
  }

  # Return the text of the specified element.
  # The element is given as a simplified XPath-like name.
  # For example, "Link/ServerOk" refers to the ServerOk element
  # nested in the Link element (nested in the root element).
  # If the element is not found, return "".
  public function get_element_text($element)
  {
      #print_r($this->xml_value_); # JH_DEBUG
    $index = $this->get_element_index($element, 0);
      if ($index == 0) {
          return "";
      } else {
          ## TW2004-09-24: Fixed bug when elemnt existent but empty
    #
    $elementObj = $this->xml_value_[$index];
          if (! array_key_exists("value", $elementObj)) {
              return "";
          }
   
          return $this->xml_value_[$index]["value"];
      }
  }

  # (internal method)
  # Return the index of the specified element,
  # relative to some given root element index.
  #
  public function get_element_index($element, $rootindex = 0)
  {
      #$element = strtoupper($element);
    $pos = strpos($element, "/");
      if ($pos !== false) {
          # element contains '/': find first part
      $start_path = substr($element, 0, $pos);
          $remain_path = substr($element, $pos+1);
          $index = $this->get_element_index($start_path, $rootindex);
          if ($index == 0) {
              # couldn't find first part; give up.
        return 0;
          }
      # recursively find rest
      return $this->get_element_index($remain_path, $index);
      } else {
          # search from the parent across all its children
      # i.e. until we get the parent's close tag.
      $level = $this->xml_value_[$rootindex]["level"];
          if ($this->xml_value_[$rootindex]["type"] == "complete") {
              return 0;   # no children
          }
          $index = $rootindex+1;
          while ($index<count($this->xml_value_) &&
             !($this->xml_value_[$index]["level"]==$level &&
               $this->xml_value_[$index]["type"]=="close")) {
              # if one below parent and tag matches, bingo
        if ($this->xml_value_[$index]["level"] == $level+1 &&
#            $this->xml_value_[$index]["type"] == "complete" &&
            $this->xml_value_[$index]["tag"] == $element) {
            return $index;
        }
              $index++;
          }
          return 0;
      }
  }
}

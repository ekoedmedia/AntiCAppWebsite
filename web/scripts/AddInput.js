/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/* 
 * Copy the input in the current button.
 */
rowNum = 0;
function addRow(button) {
    rowNum++;
    div = $(button).parent();
    input = div.find("input").first().clone();
    row = jQuery('<div/>', { class: 'row' });
    $(button).before(row);
    inputDiv = jQuery('<div/>', { class: 'col-sm-8' });
    row.append(inputDiv);
    inputDiv.append(input);
    buttonDiv = jQuery('<div/>', { class: 'col-sm-4' });
    row.append(buttonDiv);
    buttonDiv.append("<button type='button' onClick='removeRow(this);' class='btn btn-danger btn-xs'>\n\
<span class='glyphicon glyphicon-minus-sign'></span>Remove</button>");
    }
function removeRow(button)
{
   $(button).parent().parent().remove(); 
}
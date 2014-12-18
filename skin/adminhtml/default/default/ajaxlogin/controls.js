/**
 * 
 */
__landingPage_doSelect = function(selectNode, value) {
    var __success = false;
    for ( var __index = 0; __index < selectNode.options.length; __index++ ) {
        if ( selectNode.options[__index].value == value ) {
            selectNode.selectedIndex = __index;
            
            var __textInputNode = __getNearestSiblingAfter(selectNode, 'INPUT');
            __textInputNode.originalValue = null;
            
            __landingPage_onChangeHandler(selectNode);
            __success = true;
            break;
        }
    }
    
    return __success;
};


/**
 * 
 */
__landingPage_onChangeHandler = function(selectNode) {
    var __textInputNode        = __getNearestSiblingAfter(selectNode, 'INPUT');
    var __textInputCommentNode = __getNearestSiblingAfter(__textInputNode, 'P');
    
    if ( selectNode.options[selectNode.selectedIndex].value == '...' ) {
        __textInputCommentNode.style.display = '';
        __textInputNode.style.display        = '';
        if ( __textInputNode.originalValue != null ) {
            __textInputNode.value         = __textInputNode.originalValue;
            __textInputNode.originalValue = null;
        }
        else {
            __textInputNode.value = '';
        }
        __textInputNode.focus();
    }
    else {
        __textInputCommentNode.style.display = 'none';
        __textInputNode.style.display = 'none';
        if ( __textInputNode.originalValue == null ) __textInputNode.originalValue = __textInputNode.value;
        __textInputNode.value = selectNode.options[selectNode.selectedIndex].value;
    }
};


/**
 * 
 */
__insertVariable = function(inputNode, variableCode) {
    var __position = __getCursorPosition(inputNode);
    inputNode.value = inputNode.value.substr(0, __position) + variableCode + inputNode.value.substr(__position);
    __setCursorPosition(inputNode, __position + variableCode.length);
};


/**
 * 
 */
__openOptionHint = function(inputNode) {
    var __hintSpanNode = inputNode.parentNode.select('[rel="__optionHint"]')[0];
    if ( __hintSpanNode ) {
        var __optionHintPopupRootNode = $$('.al-optionHintPopup')[0];
        if( __optionHintPopupRootNode ) {
            var __contentNode = __optionHintPopupRootNode.select('.Content')[0];
            if ( __contentNode ) {
                __contentNode.innerHTML = __hintSpanNode.innerHTML;
            }
            __optionHintPopupRootNode.style.display = '';
        }
    }
};


/**
 * 
 */
__getCursorPosition = function(inputNode) {
    if ( !inputNode ) return;
    if ( 'selectionStart' in inputNode ) {
        return inputNode.selectionStart;
    }
    else if (document.selection) {
        inputNode.focus();
        var sel = document.selection.createRange();
        var selLen = document.selection.createRange().text.length;
        sel.moveStart('character', -inputNode.value.length);
        return sel.text.length - selLen;
    }
};


/**
 * 
 */
__setCursorPosition = function(inputNode, pos) {
    if ( !inputNode ) {
        return false;
    }
    else if ( inputNode.createTextRange ) {
        var textRange = inputNode.createTextRange();
        textRange.collapse(true);
        textRange.moveEnd(pos);
        textRange.moveStart(pos);
        textRange.select();
        return true;
    }
    else if ( inputNode.setSelectionRange ) {
        inputNode.setSelectionRange(pos,pos);
        return true;
    }
    
    return false;
}


/**
 * 
 */
__getNearestSiblingAfter = function(node, tagName) {
    __closestNode = node.nextSibling;
    while ( (typeof __closestNode != 'undefined') && (__closestNode.tagName != tagName) ) {
        __closestNode = __closestNode.nextSibling;
    }
    
    if ( __closestNode.tagName == tagName ) return __closestNode;
    else return null;
};


Event.observe(
    window,
    'load',
    function() {
        $$('.al-optionHintPopup').each(
            function(popupRootNode) {
                var __shadowNode = popupRootNode.select('.Shadow')[0];
                __shadowNode.observe(
                    'click',
                    function(event) {
                        popupRootNode.style.display = 'none';
                    }
                );
                
                var __closeButton = popupRootNode.select('button')[0];
                __closeButton.observe(
                    'click',
                    function(event) {
                        popupRootNode.style.display = 'none';
                    }
                );
            }
        );
    }
);
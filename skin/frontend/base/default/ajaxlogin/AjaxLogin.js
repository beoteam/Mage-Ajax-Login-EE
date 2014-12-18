if ( window.Prototype ) {
    
    /**
     * 
     */
    if ( typeof AjaxLogin == 'undefined' ) {
        AjaxLogin = {};
    }
    
    
    /**
     * 
     */
    AjaxLogin.cancelPrototypeEvent = function(event) {
        if (window.event) window.event.cancelBubble = true;
        if (window.event) window.event.returnValue = false;
        event.preventDefault();
        event.stopPropagation();
        
        return false;
    };
    
    
    /**
     * 
     */
    AjaxLogin.DOB = Class.create();
    AjaxLogin.DOB.prototype = {
        initialize: function(selector, required, format) {
            var el = $$(selector)[0];
            var container       = {};
            container.day       = Element.select(el, '#ajaxlogin-day')[0];
            container.month     = Element.select(el, '#ajaxlogin-month')[0];
            container.year      = Element.select(el, '#ajaxlogin-year')[0];
            container.full      = Element.select(el, '#ajaxlogin-full')[0];
            container.advice    = Element.select(el, '#ajaxlogin-advice')[0];
            
            new Varien.DateElement('container', container, required, format);
        }
    };
    
    
    /**
     * 
     */
    AjaxLogin.Captcha = Class.create();
    AjaxLogin.Captcha.prototype = {
        initialize: function(url, formId){
            this.url = url;
            this.formId = formId;
        },
        
        refresh: function(elem) {
            formId = this.formId;
            if (elem) Element.addClassName(elem, 'refreshing');
            new Ajax.Request(
                this.url,
                {
                    onSuccess: function (response) {
                        if (response.responseText.isJSON()) {
                            var json = response.responseText.evalJSON();
                            if (!json.error && json.imgSrc) {
                                $$('#' + formId).each( function(element) { element.writeAttribute('src', json.imgSrc); } );
                                if (elem) Element.removeClassName(elem, 'refreshing');
                            } else {
                                if (elem) Element.removeClassName(elem, 'refreshing');
                            }
                        }
                    },
                    method: 'post',
                    parameters: {
                        'formId' : this.formId,
                        'width': parseInt($$('#' + formId).first().getStyle('width')),
                        'height': parseInt($$('#' + formId).first().getStyle('height'))
                    }
                }
            );
        }
    };
}
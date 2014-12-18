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
    AjaxLogin.Authorization = Class.create();
    
    /* Frame types */
    AjaxLogin.Authorization.FRAMETYPE_CUSTOM            =  0;
    AjaxLogin.Authorization.FRAMETYPE_LOGIN             =  1;
    AjaxLogin.Authorization.FRAMETYPE_REGISTER          =  2;
    AjaxLogin.Authorization.FRAMETYPE_RECOVERY          =  3;
    AjaxLogin.Authorization.FRAMETYPE_EXTRA             =  4;
    
    /* Event types to trigger and to handle */
    AjaxLogin.Authorization.EVENTTYPE_ALL               =  0;
    AjaxLogin.Authorization.EVENTTYPE_JSONFAILURE       =  1;
    AjaxLogin.Authorization.EVENTTYPE_SERVERFAILURE     =  2;
    AjaxLogin.Authorization.EVENTTYPE_LOGINSUBMIT       =  3;
    AjaxLogin.Authorization.EVENTTYPE_LOGINSUCCESS      =  4;
    AjaxLogin.Authorization.EVENTTYPE_LOGINFAILURE      =  5;
    AjaxLogin.Authorization.EVENTTYPE_LOGOUTSUCCESS     =  6;
    AjaxLogin.Authorization.EVENTTYPE_LOGOUTFAILURE     =  7;
    AjaxLogin.Authorization.EVENTTYPE_REGISTERSUCCESS   =  8;
    AjaxLogin.Authorization.EVENTTYPE_REGISTERFAILURE   =  9;
    AjaxLogin.Authorization.EVENTTYPE_SNLOGINSUCCESS    = 10;
    AjaxLogin.Authorization.EVENTTYPE_SNLOGINFAILURE    = 11;
    AjaxLogin.Authorization.EVENTTYPE_REQUIREDENTRIES   = 12;
    AjaxLogin.Authorization.EVENTTYPE_SNREGISTERSUCCESS = 13;
    AjaxLogin.Authorization.EVENTTYPE_SNREGISTERFAILURE = 14;
    
    AjaxLogin.Authorization.prototype = {
        
        /**
         * 
         */
        initialize: function(rootNode) {
            this.__rootNode = rootNode;
            
            this.__frames = [];
            this.__currentFrame = null;
            this.__handlers = {};
            
            this.__setProgressBar( this.__rootNode.select('.alProgressBar')[0] );
            
            this.__detectFrames();
            this.switchToFrame(0);
            
            AjaxLogin.Authorization.__registerInstance(this);
        },
        
        
        /**
         * 
         */
        addFrame: function(rootNode, type, name) {
            if ( typeof type == 'undefined' ) {
                type = AjaxLogin.Authorization.FRAMETYPE_CUSTOM;
            }
            if ( typeof name == 'undefined' ) {
                switch (type) {
                    case AjaxLogin.Authorization.FRAMETYPE_CUSTOM:   name = 'custom';   break;
                    case AjaxLogin.Authorization.FRAMETYPE_LOGIN:    name = 'login';    break;
                    case AjaxLogin.Authorization.FRAMETYPE_REGISTER: name = 'register'; break;
                    case AjaxLogin.Authorization.FRAMETYPE_RECOVERY: name = 'recovery'; break;
                    case AjaxLogin.Authorization.FRAMETYPE_EXTRA:    name = 'extra';    break;
                }
            }
            if ( this.__getFrameIndexByName(name) != null ) {
                var __suffix = 0;
                while ( this.__getFrameIndexByName(name + '_' + __suffix.toString()) != null ) __suffix++;
                name += '_' + __suffix.toString();
            }
            
            var __frame = {
                rootNode : rootNode,
                type     : type,
                name     : name
            };
            
            /**********************************************************************************************
             * FRAMETYPE = LOGIN
             **********************************************************************************************/
            if ( __frame.type == AjaxLogin.Authorization.FRAMETYPE_LOGIN ) {
                __frame.loginFormNode = __frame.rootNode.select('form')[0];
                __frame.logoutFormNode = __frame.rootNode.select('form')[1];
                
                if ( __frame.loginFormNode ) {
                    __frame.validator = new Validation(
                        __frame.loginFormNode,
                        {
                            onSubmit: false,
                            onFormValidate: function(frameInstance) {
                                return function(validationResult) {
                                    frameInstance.login(validationResult);
                                };
                            }
                            (__frame)
                        }
                    );
                }
                
                __frame.login = function (authorizationInstance) {
                    return function(validationResult) {
                        if ( !this.loginFormNode ) {
                            return this;
                        }
                        
                        if ( typeof validationResult == 'undefined' ) {
                            if ( this.validator ) {
                                this.validator.validate();
                                return this;
                            }
                            else validationResult = false;
                        }
                        if ( !validationResult ) return this;
                        
                        var __parameters = authorizationInstance.__collectFormParameters( this.loginFormNode );
                        __parameters.location = window.location.href;
                        
                        authorizationInstance.__scheduleProgressBar();
                        new Ajax.Request(
                            this.loginFormNode.action,
                            {
                                method: 'post',
                                parameters: __parameters,
                                onSuccess: function(transport) {
                                    try { eval('var __response = ' + transport.responseText + ';'); }
                                    catch (__E) { __response = {}; }
                                    
                                    __response.requestParameters = __parameters;
                                    
                                    if ( typeof __response.success != 'undefined' ) {
                                        if ( __response.success == 1) {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_LOGINSUCCESS, __response);
                                        }
                                        else {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_LOGINFAILURE, __response);
                                        }
                                    }
                                    else {
                                        authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_JSONFAILURE, __response);
                                    }
                                    
                                    authorizationInstance.__makeResponseReaction(__response);
                                    authorizationInstance.__hideProgressBar();
                                },
                                onFailure: function() {
                                    authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SERVERFAILURE);
                                    authorizationInstance.__hideProgressBar();
                                }
                            }
                        );
                        
                        return this;
                    };
                }
                (this);
                
                __frame.logout = function (authorizationInstance) {
                    return function() {
                        if ( !this.logoutFormNode ) {
                            return this;
                        }
                        
                        var __parameters = {};
                        __parameters.location = window.location.href;
                        
                        authorizationInstance.__scheduleProgressBar();
                        new Ajax.Request(
                            this.logoutFormNode.action,
                            {
                                method: 'post',
                                parameters: __parameters,
                                onSuccess: function(transport) {
                                    try { eval('var __response = ' + transport.responseText + ';'); }
                                    catch (__E) { __response = {}; }
                                    
                                    __response.requestParameters = __parameters;
                                    
                                    if ( typeof __response.success != 'undefined' ) {
                                        if ( __response.success == 1) {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_LOGOUTSUCCESS, __response);
                                        }
                                        else {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_LOGOUTFAILURE, __response);
                                        }
                                    }
                                    else {
                                        authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_JSONFAILURE, __response);
                                    }
                                    
                                    authorizationInstance.__makeResponseReaction(__response);
                                    authorizationInstance.__hideProgressBar();
                                },
                                onFailure: function() {
                                    authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SERVERFAILURE);
                                    authorizationInstance.__hideProgressBar();
                                }
                            }
                        );
                        
                        return this;
                    };
                }
                (this);
                
                __frame.loginFormNode.observe(
                    'submit',
                    function(frameInstance) {
                        return function(event) {
                            frameInstance.login();
                            if (window.event) window.event.cancelBubble = true;
                            if (window.event) window.event.returnValue = false;
                            event.preventDefault();
                            event.stopPropagation();
                            return false;
                        };
                    }
                    (__frame)
                );
            }
            
            /**********************************************************************************************
             * FRAMETYPE = REGISTER
             **********************************************************************************************/
            if ( __frame.type == AjaxLogin.Authorization.FRAMETYPE_REGISTER ) {
                __frame.registrationFormNode = __frame.rootNode.select('form')[0];
                
                if ( __frame.registrationFormNode ) {
                    __frame.validator = new Validation(__frame.registrationFormNode);
                }
                
                __frame.register = function (authorizationInstance) {
                    return function(parameters) {
                        authorizationInstance.__scheduleProgressBar();
                        new Ajax.Request(
                            this.registrationFormNode.action,
                            {
                                method: 'post',
                                parameters: parameters,
                                onSuccess: function(transport) {
                                    try { eval('var __response = ' + transport.responseText + ';'); }
                                    catch (__E) { __response = {}; }
                                    
                                    __response.requestParameters = parameters;
                                    
                                    if ( typeof __response.success != 'undefined' ) {
                                        if ( __response.success == 1) {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_REGISTERSUCCESS, __response);
                                            if ( __response.loggedIn == 1) {
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_LOGINSUCCESS, __response);
                                            }
                                        }
                                        else {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_REGISTERFAILURE, __response);
                                        }
                                    }
                                    else {
                                        authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_JSONFAILURE, __response);
                                    }
                                    
                                    authorizationInstance.__makeResponseReaction(__response);
                                    authorizationInstance.__hideProgressBar();
                                },
                                onFailure: function() {
                                    authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SERVERFAILURE);
                                    authorizationInstance.__hideProgressBar();
                                }
                            }
                        );
                        
                        return this;
                    };
                }
                (this);
                
                __frame.registrationFormNode.observe(
                    'submit',
                    function(frameInstance, authorizationInstance) {
                        return function(event) {
                            if ( !frameInstance.registrationFormNode ) {
                                return false;
                            }
                            
                            if ( frameInstance.validator && !frameInstance.validator.validate() ) {
                                return false;
                            }
                            
                            var __parameters = authorizationInstance.__collectFormParameters( frameInstance.registrationFormNode );
                            frameInstance.register(__parameters);
                            
                            if (window.event) window.event.cancelBubble = true;
                            if (window.event) window.event.returnValue = false;
                            event.preventDefault();
                            event.stopPropagation();
                            return false;
                        };
                    }
                    (__frame, this)
                );
            }
            
            /**********************************************************************************************
             * FRAMETYPE = RECOVERY
             **********************************************************************************************/
            if ( __frame.type == AjaxLogin.Authorization.FRAMETYPE_RECOVERY ) {
                __frame.recoveryFormNode = __frame.rootNode.select('form')[0];
                
                if ( __frame.recoveryFormNode ) {
                    __frame.validator = new Validation(__frame.recoveryFormNode);
                }
                
                __frame.recovery = function(authorizationInstance) {
                    return function(parameters, callbackFunction) {
                        authorizationInstance.__scheduleProgressBar();
                        new Ajax.Request(
                            this.recoveryFormNode.action,
                            {
                                method: 'post',
                                parameters: parameters,
                                onSuccess: function(frameInstance) {
                                    return function(transport) {
                                        try { eval('var __response = ' + transport.responseText + ';'); }
                                        catch (__E) { __response = {}; }
                                        
                                        __response.requestParameters = parameters;
                                        
                                        if ( typeof __response.success != 'undefined' ) {
                                            if ( __response.success == 1) {
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_RECOVERYSUCCESS, __response);
                                            }
                                            else {
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_RECOVERYFAILURE, __response);
                                            }
                                        }
                                        else {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_JSONFAILURE, __response);
                                        }
                                        
                                        if ( typeof callbackFunction == 'function') callbackFunction.apply(frameInstance, [__response]);
                                        authorizationInstance.__makeResponseReaction(__response);
                                        authorizationInstance.__hideProgressBar();
                                    };
                                }
                                (this),
                                onFailure: function() {
                                    authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SERVERFAILURE);
                                    authorizationInstance.__hideProgressBar();
                                }
                            }
                        );
                    };
                }
                (this);
                
                __frame.recoveryFormNode.observe(
                    'submit',
                    function(frameInstance, authorizationInstance) {
                        return function(event) {
                            if ( !frameInstance.recoveryFormNode ) {
                                return false;
                            }
                            
                            if ( frameInstance.validator && !frameInstance.validator.validate() ) {
                                return false;
                            }
                            
                            var __parameters = authorizationInstance.__collectFormParameters( frameInstance.recoveryFormNode );
                            frameInstance.recovery(__parameters);
                            
                            if (window.event) window.event.cancelBubble = true;
                            if (window.event) window.event.returnValue = false;
                            event.preventDefault();
                            event.stopPropagation();
                            return false;
                        };
                    }
                    (__frame, this)
                );
            }
            
            /**********************************************************************************************
             * FRAMETYPE = EXTRA
             **********************************************************************************************/
            if ( __frame.type == AjaxLogin.Authorization.FRAMETYPE_EXTRA ) {
                __frame.loginFormNode        = __frame.rootNode.select('form')[0];
                __frame.registrationFormNode = __frame.rootNode.select('form')[1];
                
                if ( __frame.registrationFormNode ) {
                    __frame.validator = new Validation(__frame.registrationFormNode);
                }
                
                __frame.login = function(authorizationInstance) {
                    return function(parameters, callbackFunction) {
                        var __parameters = authorizationInstance.__collectFormParameters( __frame.loginFormNode );
                        for ( __key in parameters ) {
                            __parameters[__key] = parameters[__key];
                        }
                        
                        authorizationInstance.__scheduleProgressBar();
                        new Ajax.Request(
                            this.loginFormNode.action,
                            {
                                method: 'post',
                                parameters: __parameters,
                                onSuccess: function(frameInstance) {
                                    return function(transport) {
                                        try { eval('var __response = ' + transport.responseText + ';'); }
                                        catch (__E) { __response = {}; }
                                        
                                        __response.requestParameters = __parameters;
                                        
                                        if ( typeof __response.success != 'undefined' ) {
                                            if ( __response.success == 1) {
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SNLOGINSUCCESS, __response);
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_LOGINSUCCESS, __response);
                                            }
                                            else {
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SNLOGINFAILURE, __response);
                                            }
                                        }
                                        else {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_JSONFAILURE, __response);
                                        }
                                        
                                        authorizationInstance.__makeResponseReaction(__response);
                                        if ( typeof callbackFunction == 'function') {
                                            try { callbackFunction.apply(frameInstance, [__response]); }
                                            catch ( __E ) {  }
                                        }
                                        authorizationInstance.__hideProgressBar();
                                    };
                                }
                                (this),
                                onFailure: function() {
                                    authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SERVERFAILURE);
                                    authorizationInstance.__hideProgressBar();
                                }
                            }
                        );
                    };
                }
                (this);
                
                __frame.register = function(authorizationInstance) {
                    return function(parameters, callbackFunction) {
                        if ( typeof parameters != 'object' ) parameters = {};
                        if ( typeof parameters.password == 'undefined' ) {
                            var __string = authorizationInstance.__getRandomString(16, '#aA!');
                            
                            parameters.password     = __string;
                            parameters.confirmation = __string;
                        }
                        
                        var __registerFrame = authorizationInstance.getFrame('register');
                        if ( __registerFrame ) {
                            var __providingListNodes    = Element.select(__registerFrame.rootNode, 'ul')[0].children;
                            var __acceptingListNode     = this.rootNode.select('ul')[0];
                            var __acceptingListElements = __acceptingListNode.children;
                            
                            var __length = __acceptingListElements.length;
                            for ( var __index = __length - 2; __index > 0; __index-- ) {
                                var __node = __acceptingListElements.item(__index);
                                __acceptingListNode.removeChild(__node);
                            }
                            
                            var __formHasRequiredUnspecifiedEntries = false;
                            var __nearestUnspecifiedElementNode     = null;
                            for ( var __listItemIndex = 0; __listItemIndex < __providingListNodes.length; __listItemIndex++ ) {
                                var __elements = __providingListNodes[__listItemIndex].select('input').concat(
                                    __providingListNodes[__listItemIndex].select('select')
                                );
                                
                                if ( __elements.length ) {
                                    var __liNode = document.createElement('LI');
                                    __liNode.innerHTML = __providingListNodes[__listItemIndex].innerHTML;
                                    
                                    var __elementHasRequiredUnspecifiedEnties = false;
                                    for ( var __index = 0; __index < __elements.length; __index++ ) {
                                        var __elementNode    = __liNode.select('[name=' + __elements[__index].name + ']')[0];
                                        
                                        if ( __elementNode ) {
                                            var __elementName    = __elementNode.name;
                                            var __elementTagname = __elementNode.tagName;
                                            var __elementType    = __elementNode.type;
                                            
                                            var __candidate = __elementNode;
                                            var __elementLabelNode = null;
                                            while ( (typeof __candidate.parentNode != 'undefined') && (__candidate.parentNode) ) {
                                                if ( __candidate.parentNode.tagName == 'LI' ) {
                                                    __elementLabelNode = Element.select(__candidate.parentNode, 'label[for=' + __elementName + ']')[0];
                                                    break;
                                                }
                                                
                                                __candidate = __candidate.parentNode;
                                            }
                                            
                                            if ( (__elementName) && (typeof parameters[__elementName] != 'undefined') && (parameters[__elementName]) ) {
                                                if ( (__elementTagname == 'INPUT') && ( (!__elementType) || (__elementType == 'text') || (__elementType == 'password') ) ) {
                                                    __elementNode.value = parameters[__elementName];
                                                }
                                            }
                                            else {
                                                if ( __elementNode.hasClassName('required-entry') || ( (__elementLabelNode) && (__elementLabelNode.hasClassName('required')) ) ) {
                                                    __elementHasRequiredUnspecifiedEnties = true;
                                                    __nearestUnspecifiedElementNode = __elementNode;
                                                }
                                            }
                                        }
                                    }
                                    if ( __elementHasRequiredUnspecifiedEnties ) {
                                        __formHasRequiredUnspecifiedEntries = true;
                                    }
                                    else {
                                        __liNode.style.display = 'none';
                                    }
                                    
                                    __acceptingListNode.insertBefore(__liNode, __acceptingListNode.select('li')[__acceptingListNode.select('li').length - 1]);
                                    __liNode.innerHTML.evalScripts();
                                    if ( __liNode.select('.ajaxlogin-customer-dob').length > 0 ) {
                                        var __divNode = __liNode.select('.ajaxlogin-customer-dob')[0];
                                        __divNode.removeClassName('ajaxlogin-customer-dob');
                                        __divNode.addClassName('ajaxlogin-customer-dob-extra');
                                        new AjaxLogin.DOB('.ajaxlogin-customer-dob-extra', 'true', AjaxLogin.DATE_FORMAT);
                                    }
                                }
                            }
                            if ( typeof parameters.accessToken != 'undefined' ) {
                                var __liNode = document.createElement('LI');
                                var __node = document.createElement('INPUT');
                                __node.type = 'hidden';
                                __node.name = 'accessToken';
                                __node.value = parameters.accessToken;
                                __liNode.appendChild(__node);
                                __acceptingListNode.insertBefore(__liNode, __acceptingListNode.select('li')[__acceptingListNode.select('li').length - 1]);
                            }
                            if ( typeof parameters.network != 'undefined' ) {
                                var __liNode = document.createElement('LI');
                                var __node = document.createElement('INPUT');
                                __node.type = 'hidden';
                                __node.name = 'network';
                                __node.value = parameters.network;
                                __liNode.appendChild(__node);
                                __acceptingListNode.insertBefore(__liNode, __acceptingListNode.select('li')[__acceptingListNode.select('li').length - 1]);
                            }
                            
                            if ( __formHasRequiredUnspecifiedEntries ) {
                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_REQUIREDENTRIES);
                                authorizationInstance.switchToFrame(this.name);
                                __nearestUnspecifiedElementNode.focus();
                            }
                            else {
                                this.commitRegistration(parameters);
                            }
                        }
                    };
                }
                (this);
                
                __frame.commitRegistration = function(authorizationInstance) {
                    return function(parameters) {
                        authorizationInstance.__scheduleProgressBar();
                        new Ajax.Request(
                            this.registrationFormNode.action,
                            {
                                method: 'post',
                                parameters: parameters,
                                onSuccess: function(transport) {
                                    try { eval('var __response = ' + transport.responseText + ';'); }
                                    catch (__E) { __response = {}; }
                                    
                                    __response.requestParameters = parameters;
                                    
                                    if ( typeof __response.success != 'undefined' ) {
                                        if ( __response.success == 1) {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SNREGISTERSUCCESS, __response);
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_REGISTERSUCCESS, __response);
                                            if ( __response.loggedIn == 1) {
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SNLOGINSUCCESS, __response);
                                                authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_LOGINSUCCESS, __response);
                                            }
                                        }
                                        else {
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SNREGISTERFAILURE, __response);
                                            authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_REGISTERFAILURE, __response);
                                        }
                                    }
                                    else {
                                        authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_JSONFAILURE, __response);
                                    }
                                    
                                    authorizationInstance.__makeResponseReaction(__response);
                                    authorizationInstance.__hideProgressBar();
                                },
                                onFailure: function() {
                                    authorizationInstance.trigger(AjaxLogin.Authorization.EVENTTYPE_SERVERFAILURE);
                                    authorizationInstance.__hideProgressBar();
                                }
                            }
                        );
                        
                        return this;
                    };
                }
                (this);
                
                __frame.registrationFormNode.observe(
                    'submit',
                    function(frameInstance, authorizationInstance) {
                        return function(event) {
                            if ( !frameInstance.registrationFormNode ) {
                                return false;
                            }
                            
                            if ( frameInstance.validator && !frameInstance.validator.validate() ) {
                                return false;
                            }
                            
                            var __parameters = authorizationInstance.__collectFormParameters( frameInstance.registrationFormNode );
                            frameInstance.commitRegistration(__parameters);
                            
                            if (window.event) window.event.cancelBubble = true;
                            if (window.event) window.event.returnValue = false;
                            event.preventDefault();
                            event.stopPropagation();
                            return false;
                        };
                    }
                    (__frame, this)
                );
            }
            
            __frame.forceUpdating = function() {
                __frame.rootNode.select('form').each(
                    function(formNode) {
                        var __inputNode = document.createElement('INPUT');
                        __inputNode.type  = 'hidden';
                        __inputNode.name  = '__forceUpdating';
                        __inputNode.value = '1';
                        formNode.insert(__inputNode);
                    }
                );
            };
            
            this.__frames.push(__frame);
        },
        
        
        /**
         * 
         */
        addLoginFrame: function(rootNode, name) {
            return this.addFrame(rootNode, AjaxLogin.Authorization.FRAMETYPE_LOGIN, name);
        },
        
        
        /**
         * 
         */
        addRegisterFrame: function(rootNode, name) {
            return this.addFrame(rootNode, AjaxLogin.Authorization.FRAMETYPE_REGISTER, name);
        },
        
        
        /**
         * 
         */
        addRecoveryFrame: function(rootNode, name) {
            return this.addFrame(rootNode, AjaxLogin.Authorization.FRAMETYPE_RECOVERY, name);
        },
        
        
        /**
         * 
         */
        addExtraFrame: function(rootNode, name) {
            return this.addFrame(rootNode, AjaxLogin.Authorization.FRAMETYPE_EXTRA, name);
        },
        
        
        /**
         * 
         */
        getFrame: function(frame) {
            var __frame = null;
            var __frameIndex = this.__getFrameIndex(frame);
            if ( typeof __frameIndex == 'number' ) {
                __frame = this.__frames[__frameIndex];
            }
            
            return __frame;
        },
        
        
        /**
         * 
         */
        getCurrentFrame: function() {
            return this.getFrame(this.__currentFrame);
        },
        
        
        /**
         * 
         */
        switchToFrame: function(frame) {
            var __frameIndex = this.__getFrameIndex(frame);
            
            if ( typeof __frameIndex == 'number' ) {
                this.__hideFrame(this.__currentFrame);
                this.__showFrame(__frameIndex);
                this.__currentFrame = __frameIndex;
                
                var __frame = this.getFrame(__frameIndex);
                var __messagesNode = __frame.rootNode.select('ul.messages')[0];
                if ( __messagesNode ) __messagesNode.style.display = 'none';
            }
            
            return this;
        },
        
        
        /**
         * 
         */
        addHandler: function(eventType, handlingFunction) {
            if ( (typeof eventType == 'object') && (typeof eventType.length == 'number') ) {
                for ( var __index = 0; __index < eventType.length; __index++ ) {
                    this.addHandler(eventType[__index], handlingFunction);
                }
                
                return this;
            }
            
            if ( typeof this.__handlers[eventType] == 'undefined' ) {
                this.__handlers[eventType] = [];
            }
            this.__handlers[eventType].push(handlingFunction);
            
            return this;
        },
        
        
        /**
         * 
         */
        trigger: function(eventType, eventData) {
            if ( typeof this.__handlers[eventType] != 'undefined' ) {
                for ( var __index = 0; __index < this.__handlers[eventType].length; __index++ ) {
                    var __handlingFunction = this.__handlers[eventType][__index];
                    __handlingFunction.apply(this, [eventData]);
                }
            }
            if ( typeof this.__handlers[AjaxLogin.Authorization.EVENTTYPE_ALL] != 'undefined' ) {
                for ( var __index = 0; __index < this.__handlers[AjaxLogin.Authorization.EVENTTYPE_ALL].length; __index++ ) {
                    var __handlingFunction = this.__handlers[AjaxLogin.Authorization.EVENTTYPE_ALL][__index];
                    __handlingFunction.apply(this, [eventData, eventType]);
                }
            }
        },
        
        
        /**
         * 
         */
        setMessage: function(message, type, delay) {
            if ( typeof type != 'number' ) type = 0;
            
            var __frame = this.getCurrentFrame();
            var __messagesNode = __frame.rootNode.select('ul.messages')[0];
            
            if ( this.__messagesTimeOut ) clearTimeout(this.__messagesTimeOut);
            __messagesNode.style.display = 'none';
            
            while (__messagesNode.firstChild) __messagesNode.removeChild(__messagesNode.firstChild);
            var __messageNode = document.createElement('LI');
            __messagesNode.appendChild(__messageNode);
            
            if ( type == 1 ) {
                __messageNode.className = 'success-msg';
            }
            else {
                __messageNode.className = 'error-msg';
            }
            
            __messageNode.innerHTML = message;
            
            __messagesNode.style.display = '';
            
            this.__messagesTimeOut = setTimeout(
                function(messagesNode) {
                    return function() {
                        messagesNode.style.display = 'none';
                    };
                }
                (__messagesNode),
                ( typeof delay != 'undefined' ? delay * 1000 : 10000)
            );
            
            return this;
        },
        
        
        /**
         * 
         */
        __collectFormParameters: function(formNode) {
            var __parameters = {};
            
            formNode.getElements().each(
                function(element) {
                    if ( !((typeof element.type == 'undefined') || ((element.type == 'checkbox') && (!element.checked))) ) {
                        var __value = typeof element.value != 'undefined' ? element.value : 1;
                        
                        if ( typeof __parameters[element.name] != 'undefined' ) {
                            if ( (typeof __parameters[element.name].length == 'undefined') || (typeof __parameters[element.name].push == 'undefined') ) {
                                var __array = [];
                                __array.push( __parameters[element.name] );
                                __parameters[element.name] = __array;
                            }
                            
                            __parameters[element.name].push(element.value);
                        }
                        else {
                            __parameters[element.name] = __value;
                        }
                    }
                }
            );
            
            return __parameters;
        },
        
        
        /**
         * 
         */
        __detectFrames: function() {
            var __frames = this.__rootNode.select('.Frame');
            for ( var __index = 0; __index < __frames.length; __index++ ) {
                if ( __frames[__index].select('.al-authorizationform-login').length > 0 ) {
                    this.addLoginFrame(__frames[__index]);
                }
                else if ( __frames[__index].select('.al-authorizationform-register').length > 0 ) {
                    this.addRegisterFrame(__frames[__index]);
                }
                else if ( __frames[__index].select('.al-authorizationform-recovery').length > 0 ) {
                    this.addRecoveryFrame(__frames[__index]);
                }
                else if ( __frames[__index].select('.al-authorizationform-extra').length > 0 ) {
                    this.addExtraFrame(__frames[__index]);
                }
                else {
                    this.addFrame(__frames[__index]);
                }
            }
            
            return this;
        },
        
        
        /**
         * 
         */
        __getFrameIndex: function(frame) {
            var __frameIndex = null;
            
            __frameIndex = this.__getFrameIndexByNumber(frame);
            if ( __frameIndex == null ) __frameIndex = this.__getFrameIndexByName(frame);
            if ( __frameIndex == null ) __frameIndex = this.__getFrameIndexByAssociation(frame);
            
            return __frameIndex;
        },
        
        
        /**
         * 
         */
        __getFrameIndexByNumber: function(number) {
            var __frameIndex = null;
            
            if ( typeof number == 'number' ) {
                if ( (number >= 0) && (number <= this.__frames.length) ) {
                    __frameIndex = number;
                }
            }
            
            return __frameIndex;
        },
        
        
        /**
         * 
         */
        __getFrameIndexByName: function(name) {
            var __frameIndex = null;
            
            if ( typeof name == 'string' ) {
                for ( var __index = 0; __index < this.__frames.length; __index++ ) {
                    if ( this.__frames[__index].name == name ) {
                        __frameIndex = __index;
                        break;
                    }
                }
            }
            
            return __frameIndex;
        },
        
        
        /**
         * 
         */
        __getFrameIndexByAssociation: function(association) {
            var __frameIndex = null;
            
            if ( typeof association == 'string' ) {
                for ( var __index = 0; __index < this.__frames.length; __index++ ) {
                    if ( this.__frames[__index].name.indexOf(association) !== false ) {
                        __frameIndex = __index;
                        break;
                    }
                }
            }
            
            return __frameIndex;
        },
        
        
        /**
         * 
         */
        __showFrame: function(frame) {
            var __frameIndex = this.__getFrameIndex(frame);
            
            if ( typeof __frameIndex == 'number' ) {
                var __frameRootNode = this.__frames[__frameIndex].rootNode;
                __frameRootNode.style.display = '';
            }
            
            return this;
        },
        
        
        /**
         * 
         */
        __hideFrame: function(frame) {
            var __frameIndex = this.__getFrameIndex(frame);
            
            if ( typeof __frameIndex == 'number' ) {
                var __frameRootNode = this.__frames[__frameIndex].rootNode;
                __frameRootNode.style.display = 'none';
            }
            
            return this;
        },
        
        
        /**
         * 
         */
        __getProgressBar: function() {
            return this.__progressBarNode;
        },
        
        
        /**
         * 
         */
        __setProgressBar: function(progressBarNode) {
            if ( typeof progressBarNode != 'undefined' ) {
                this.__progressBarNode = progressBarNode;
            }
            
            return this;
        },
        
        
        /**
         * 
         */
        __showProgressBar: function() {
            if ( this.__getProgressBar() ) {
                this.__getProgressBar().style.display = '';
            }
            
            return this;
        },
        
        
        /**
         * 
         */
        __hideProgressBar: function() {
            if ( this.__progressBarTimeout ) {
                clearTimeout(this.__progressBarTimeout);
            }
            if ( this.__getProgressBar() ) {
                this.__getProgressBar().style.display = 'none';
            }
            
            return this;
        },
        
        
        /**
         * 
         */
        __scheduleProgressBar: function() {
            this.__hideProgressBar();
            this.__progressBarTimeout = setTimeout(
                function(instance) {
                    return function() {
                        this.__progressBarTimeout = null;
                        instance.__showProgressBar();
                    };
                }
                (this),
                500
            );
        },
        
        
        /**
         * 
         */
        __makeResponseReaction: function(response) {
            if ( typeof response != 'undefined' ) {
                if ( typeof response.frame != 'undefined' ) {
                    this.switchToFrame(response.frame);
                }
                if ( typeof response.pageUpdates != 'undefined' ) {
                    for ( __elementName in response.pageUpdates ) {
                        var __selection       = response.pageUpdates[__elementName].selection;
                        var __inner           = response.pageUpdates[__elementName].inner;
                        var __force           = response.pageUpdates[__elementName].force;
                        var __after           = response.pageUpdates[__elementName].after;
                        var __update          = response.pageUpdates[__elementName].update;
                        var __updateSelection = response.pageUpdates[__elementName].update_selection;
                        
                        if ( (typeof __selection != 'undefined') && (typeof __update != 'undefined') ) {
                            var __matches = $$(__selection);
                            if ( __matches.length > 0 ) {
                                if ( typeof __updateSelection != 'undefined' ) {
                                    var __tmpNode = document.createElement('DIV');
                                    __tmpNode.innerHTML = __update;
                                    var __piece = __tmpNode.select(__updateSelection)[0];
                                    if ( __piece ) __update = __piece.innerHTML;
                                }
                                
                                if ( (typeof __inner != 'undefined') && (__inner) ) {
                                    __matches[0].innerHTML = __update;
                                }
                                else {
                                    __matches[0].outerHTML = __update;
                                }
                                __update.evalScripts();
                            }
                            else {
                                if ( (typeof __force != 'undefined') && (__force) ) {
                                    while ( __selection.lastIndexOf(' ') > -1 ) {
                                        var __spacePos = __selection.lastIndexOf(' ');
                                        var __parentSelection = __selection.substring(0, __spacePos);
                                        var __elementSelection = __selection.substring(__spacePos + 1);
                                        
                                        var __parentNodes = $$(__parentSelection);
                                        if ( __parentNodes.length ) {
                                            var __parentNode = __parentNodes[0];
                                            
                                            var __beforeNode;
                                            if ( (typeof __after != 'undefined') && (__after) ) {
                                                var __afterNode;
                                                if ( __parentNode.select(__after).length ) {
                                                    __afterNode = __parentNode.select(__after)[0];
                                                }
                                                
                                                if ( __afterNode ) {
                                                    __beforeNode = __afterNode.nextSibling;
                                                }
                                            }
                                            
                                            var __node = document.createElement('DIV');
                                            __parentNode.insertBefore(__node, __beforeNode);
                                            if ( (typeof __inner != 'undefined') && (__inner) ) {
                                                __node.innerHTML = __update;
                                                if ( __elementSelection.substring(0, 1) == '.' ) {
                                                    __node.className = __elementSelection.substring(1);
                                                }
                                            }
                                            else {
                                                __node.outerHTML = __update;
                                            }
                                            
                                            break;
                                        }
                                        else {
                                            __selection = __parentSelection;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ( typeof response.landing == 'string' ) {
                    window.location.href = response.landing;
                }
                if ( typeof response.errorMessage != 'undefined' ) {
                    this.setMessage(response.errorMessage, 0);
                }
                if ( typeof response.successMessage != 'undefined' ) {
                    this.setMessage(response.successMessage, 1, 30);
                }
                if (typeof(AW_AjaxCartPro) != 'undefined') {
                    AW_AjaxCartPro.stopObservers();
                    AW_AjaxCartPro.startObservers();
                }
            }
        },
        
        
        /**
         * 
         */
        __getRandomString: function(length, sets) {
            var __mask = '';
            if (sets.indexOf('a') > -1) __mask += 'abcdefghijklmnopqrstuvwxyz';
            if (sets.indexOf('A') > -1) __mask += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            if (sets.indexOf('#') > -1) __mask += '0123456789';
            if (sets.indexOf('!') > -1) __mask += '~`!@#$%^&*()_+-={}[]:";\'<>?,./|\\';
            var __result = '';
            for ( var __index = length; __index > 0; --__index ) {
                __result += __mask[Math.round(Math.random() * (__mask.length - 1))];
            }
            
            return __result;
        }
    };
    
    
    /**
     * 
     */
    AjaxLogin.Authorization.__instances = [];
    
    
    /**
     * 
     */
    AjaxLogin.Authorization.__registerInstance = function(instance) {
        AjaxLogin.Authorization.__instances.push(instance);
    };
    
    
    /**
     * 
     */
    AjaxLogin.Authorization.__getInstanceByChildnode = function(node) {
        var __instance;
        
        while ( (typeof node != 'undefined') && (node) ) {
            for ( var __index = 0; __index < AjaxLogin.Authorization.__instances.length; __index++ ) {
                if (AjaxLogin.Authorization.__instances[__index].__rootNode == node ) {
                    __instance = AjaxLogin.Authorization.__instances[__index];
                    break;
                }
            }
            node = node.parentNode;
        }
        
        return __instance;
    };
}
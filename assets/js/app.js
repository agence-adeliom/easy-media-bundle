import Vue from 'vue'
import notie from 'notie'

window.Vue = Vue;
require('./manager')
Vue.component('EasyMediaModal', require('./components/easy-media-modal').default)
Vue.component('EasyMediaDisplay', require('./components/easy-media-display').default)


function dynamicallyLoadScript(url) {
    var script = document.createElement("script");
    script.src = url;
    document.head.appendChild(script);
}

dynamicallyLoadScript("//cdnjs.cloudflare.com/ajax/libs/camanjs/4.1.2/caman.full.min.js");

if (!String.prototype.includes) {
    String.prototype.includes = function(search, start) {
        'use strict';
        if (typeof start !== 'number') {
            start = 0;
        }

        if (start + search.length > this.length) {
            return false;
        } else {
            return this.indexOf(search, start) !== -1;
        }
    };
}

window.loadMediaManger = function(event, widgetId = null) {


    window.EventHub.listen("showNotif", (obj) => {
        const types = {
            "danger": "error",
            "info": "info",
            "success": "success",
            "warning": "warning",
            "link": "neutral",
        }
        let type = types[obj.type] ? types[obj.type] : types.link;
        let duration = obj.duration ? obj.duration : 5;
        notie.alert({type: type, text: '<small>' + obj.body + '</small>', time: duration})
    })


    document.querySelectorAll("#media-holder").forEach((elm) => {
        new Vue({ el: elm })
    })

    if(widgetId){
        document.querySelectorAll(".easy-media-widget[data-widget='"+widgetId+"']").forEach((elm) => {
            new Vue({ el: elm })
        })
    }
}



window.addEventListener("DOMContentLoaded", loadMediaManger);


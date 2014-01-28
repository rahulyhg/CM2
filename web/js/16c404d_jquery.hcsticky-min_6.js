// jQuery hcSticky
// =============
// Version: 1.1.96
// Copyright: Some Web Media
// Author: Some Web Guy
// Author URL: http://twitter.com/some_web_guy
// Website: http://someweblog.com/
// Plugin URL: http://someweblog.com/hcsticky-jquery-floating-sticky-plugin/
// License: Released under the MIT License www.opensource.org/licenses/mit-license.php
// Description: Makes elements on your page float as you scroll

(function(b,k){var c=function(){var b=window.pageXOffset!==k?window.pageXOffset:window.document.documentElement.scrollLeft,e=window.pageYOffset!==k?window.pageYOffset:window.document.documentElement.scrollTop;"undefined"==typeof c.x&&(c.x=b,c.y=e);"undefined"==typeof c.distanceX?(c.distanceX=b,c.distanceY=e):(c.distanceX=b-c.x,c.distanceY=e-c.y);var f=c.x-b,d=c.y-e;c.direction=0>f?"right":0<f?"left":0>=d?"down":0<d?"up":"first";c.x=b;c.y=e};b(window).on("scroll",c);var e=function(b,c){"undefined"==typeof b.cssClone&&(b.cssClone=b.clone().css("display","none"),b.cssClone.find("input:radio").attr("name","sfd4fgdf"),b.after(b.cssClone));var e=b.cssClone[0];if("undefined"!=typeof c){var d;e.currentStyle?d=e.currentStyle[c.replace(/-\w/g,function(b){return b.toUpperCase().replace("-","")})]:window.getComputedStyle&&(d=document.defaultView.getComputedStyle(e,null).getPropertyValue(c));d=/margin/g.test(c)?parseInt(d)===b[0].offsetLeft?d:"auto":d}return{value:d||null,remove:function(){b.cssClone.remove()}}};b.fn.extend({hcSticky:function(k,w){if(0==this.length)return this;var f=k||{},d=this.data("hcSticky")?!0:!1,p=b(window),t=b(document);if("string"==typeof f){switch(f){case "reinit":return p.off("scroll",this.data("hcSticky").f),this.hcSticky({},!0);case "off":this.data("hcSticky",b.extend(this.data("hcSticky"),{on:!1}));break;case "on":this.data("hcSticky",b.extend(this.data("hcSticky"),{on:!0}))}return this}return"object"==typeof f&&(d?this.data("hcSticky",b.extend(this.data("hcSticky"),f)):(this.data("hcSticky",b.extend({top:0,bottom:0,bottomEnd:0,bottomLimiter:null,innerTop:0,innerSticker:null,className:"sticky",wrapperClassName:"wrapper-sticky",noContainer:!1,parent:null,responsive:!0,followScroll:!0,onStart:function(){},onStop:function(){},on:!0},f)),f=this.data("hcSticky").bottomLimiter,null!==f&&this.data("hcSticky").noContainer&&this.data("hcSticky",b.extend(this.data("hcSticky"),{bottomEnd:t.height()-b(f).offset().top}))),d&&!w)?this:this.each(function(){var a=b(this),d=a.data("hcSticky").parent?b(a.data("hcSticky").parent):a.parent(),g=function(){var b=a.parent("."+a.data("hcSticky").wrapperClassName);return 0<b.length?(b.css({height:a.outerHeight(!0),width:function(){var c=e(b,"width").value;e(b).remove();return 0<=c.indexOf("%")||"auto"==c?(a.css("width",b.width()),c):a.outerWidth(!0)}()}),b):!1}()||function(){var c=b("<div>",{"class":a.data("hcSticky").wrapperClassName}).css({height:a.outerHeight(!0),width:function(){var b=e(a,"width").value;return 0<=b.indexOf("%")||"auto"==b?(a.css("width",parseFloat(a.css("width"))),b):"auto"==e(a,"margin-left").value?a.outerWidth():a.outerWidth(!0)}(),margin:e(a,"margin-left").value?"auto":null,position:function(){var b=a.css("position");return"static"==b?"relative":b}(),"float":a.css("float")||null,left:e(a,"left").value,right:e(a,"right").value,top:e(a,"top").value,bottom:e(a,"bottom").value});a.wrap(c);return a.parent()}(),f=function(b){a.hasClass(a.data("hcSticky").className)||(b=b||{},a.css({position:"fixed",top:b.top||0,left:b.left||g.offset().left}).addClass(a.data("hcSticky").className),a.data("hcSticky").onStart.apply(this))},k=function(b){b=b||{};a.css({position:b.position||"absolute",top:b.top||0,left:b.left||0}).removeClass(a.data("hcSticky").className);a.data("hcSticky").onStop.apply(this)};e(a).remove();a.css({top:"auto",bottom:"auto",left:"auto",right:"auto"});if(a.outerHeight(!0)>d.height())return this;b(window).load(function(){a.outerHeight(!0)>d.height()&&(g.css("height",a.outerHeight(!0)),a.hcSticky("reinit"))});var m=!1,h=!1;p.on("resize",function(){a.data("hcSticky").responsive&&(h||(h=a.clone().attr("style","").css({visibility:"hidden",height:0,overflow:"hidden",paddingTop:0,paddingBottom:0,marginTop:0,marginBottom:0}),g.after(h)),e(h,"width").value!=e(g,"width").value&&g.width(e(h,"width").value),e(g).remove(),m&&clearTimeout(m),m=setTimeout(function(){m=!1;e(h).remove();h.remove();h=!1},100));"fixed"==a.css("position")?a.css("left",g.offset().left):a.css("left",0);a.width()!=g.width()&&a.css("width",g.width())});a.data("hcSticky",b.extend(a.data("hcSticky"),{f:function(){}}));a.data("hcSticky",b.extend(a.data("hcSticky"),{f:function(e){$referrer=a.data("hcSticky").noContainer?t:a.data("hcSticky").parent?b(a.data("hcSticky").parent):g.parent();if(a.data("hcSticky").on&&!(a.outerHeight(!0)>=$referrer.height())){var d=a.data("hcSticky").innerSticker?b(a.data("hcSticky").innerSticker).position().top:a.data("hcSticky").innerTop?a.data("hcSticky").innerTop:0,h=g.offset().top,m=$referrer.height()-a.data("hcSticky").bottomEnd+(a.data("hcSticky").noContainer?0:h),r=g.offset().top-a.data("hcSticky").top+d,l=a.outerHeight(!0)+a.data("hcSticky").bottom,n=p.height(),s=p.scrollTop(),q=a.offset().top,u=q-s,v;s>=r?m+a.data("hcSticky").bottom-(a.data("hcSticky").followScroll?0:a.data("hcSticky").top)<=s+l-d-(l-d>n-(r-d)&&a.data("hcSticky").followScroll?0<(v=l-n-d)?v:0:0)?k({top:m-l+a.data("hcSticky").bottom-h}):l-d>n&&a.data("hcSticky").followScroll?u+l<=n?"down"==c.direction?f({top:n-l}):0>u&&"fixed"==a.css("position")&&k({top:q-(r+a.data("hcSticky").top-d)-c.distanceY}):"up"==c.direction&&q>=s+a.data("hcSticky").top-d?f({top:a.data("hcSticky").top-d}):"down"==c.direction&&(q+l>n&&"fixed"==a.css("position"))&&k({top:q-(r+a.data("hcSticky").top-d)-c.distanceY}):f({top:a.data("hcSticky").top-d}):k();!0===e&&a.css("top","fixed"==a.css("position")?a.data("hcSticky").top-d:0)}}}));a.data("hcSticky").f(!0);p.on("scroll",a.data("hcSticky").f)})}})})(jQuery);
        
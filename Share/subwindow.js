
/**
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */



function subwindow(h, w, name, url) {
	var me;
	if (name=="_self") me=window;
	else me = windowExist(name, true);

	if (! me) {
		if(window.parent.Ext){
			window.parent.Ext.fdl.Interface.prototype.publish('openurl',url,name,{height: h, width: w, opener:window});			
			//return me;
		} else {

			var screen_width, screen_height;
			var win_top, win_left;
			var HelpWin;
			screen_height        = 0;     screen_width      = 0;
			win_top              = 0;     win_left          = 0;
			if (window.innerWidth) screen_width = window.innerWidth;
			if (window.innerHeight) screen_height = window.innerHeight;
			win_top  = screen_height - h - 20;
			win_left = screen_width  - w  - 20;

			me  = window.open(
					url,
					name,
					'resizable=yes,scrollbars=yes,width='+w+',height='+h+',top='+win_top+',left='+win_left);
			if (!me) {
				if (confirm("Ouverture fenêtre impossible.\nVoulez vous affichez la page dans la fenêtre courante ?")) {
					window.location.href=url;
				}
			} else {
				getConnexeWindows(me);
			}
		} 
	} else {
		me.location.href=url;
	}
	if (me) {
		me.focus();
	}

	return me;

}



// with menu
function subwindowm(h, w, name, url) {

   var screen_width, screen_height;
   var win_top, win_left;
   var HelpWin;
   screen_height        = 0;     screen_width      = 0;
   win_top              = 0;     win_left          = 0;
   if (window.innerWidth) screen_width = window.innerWidth;
   if (window.innerHeight) screen_height = window.innerHeight;
   win_top  = screen_height - h - 20;
   win_left = screen_width  - w  - 20;
   me = windowExist(name, true);
   if (! me) {
	   if(window.parent.Ext){
			window.parent.Ext.fdl.Interface.prototype.publish('openurl',url,name,{height: h, width: w, opener:window});			
			//return me;
		} else {
		     me  = window.open(
				       url,
				       name,
				       'menubar=yes,resizable=yes,scrollbars=yes,width='+w+',height='+h+',top='+win_top+',left='+win_left);
		     
		     if (!me) {
		       if (confirm("Ouverture fenêtre impossible.\nVoulez vous affichez la page dans la fenêtre courante ?")) {
			 window.location.href=url;
		       }
		     } else {
		       getConnexeWindows(me);
		     }
		}
   } else {
     me.location.href=url;
   }
   if (me) me.focus();

    
  return me;
}

// Open+close a window with a asdav:// URL
function asdavLaunch(url) {
	var w = window.open(url);
	_asdavClose(w, 2000, 1000);
}
function _asdavClose(w, firstDelay, retryDelay) {
	if (w.closed) {
		return;
	}
	if (retryDelay <= 0 ) {
		retryDelay = 1000;
	}
	if (firstDelay > 0) {
		setTimeout(function(){_asdavClose(w, 0, retryDelay);}, firstDelay);
	} else {
		w.close();
		setTimeout(function(){_asdavClose(w, 0, retryDelay);}, retryDelay);
	}
}
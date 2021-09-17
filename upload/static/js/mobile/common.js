var supporttouch = "ontouchend" in document;
!supporttouch && (window.location.href = 'forum.php?mobile=1');

var platform = navigator.platform;
var ua = navigator.userAgent;
var ios = /iPhone|iPad|iPod/.test(platform) && ua.indexOf( "AppleWebKit" ) > -1;
var andriod = ua.indexOf( "Android" ) > -1;

var JSLOADED = [];

var HTML5PLAYER = [];
HTML5PLAYER['apload'] = 0;
HTML5PLAYER['dpload'] = 0;
HTML5PLAYER['flvload'] = 0;

var BROWSER = {};
var USERAGENT = navigator.userAgent.toLowerCase();
browserVersion({'ie':'msie','firefox':'','chrome':'','opera':'','safari':'','mozilla':'','webkit':'','maxthon':'','qq':'qqbrowser','rv':'rv'});
if(BROWSER.safari || BROWSER.rv) {
	BROWSER.firefox = true;
}
BROWSER.opera = BROWSER.opera ? opera.version() : 0;

var page = {
	converthtml : function() {
		var prevpage = qSel('div.pg .prev') ? qSel('div.pg .prev').href : undefined;
		var nextpage = qSel('div.pg .nxt') ? qSel('div.pg .nxt').href : undefined;
		var lastpage = qSel('div.pg label span') ? (qSel('div.pg label span').innerText.replace(/[^\d]/g, '') || 0) : 0;
		var curpage = qSel('div.pg input') ? qSel('div.pg input').value : 1;

		if(!lastpage) {
			prevpage = qSel('div.pg .pgb a') ? qSel('div.pg .pgb a').href : undefined;
		}

		var prevpagehref = nextpagehref = '';
		if(prevpage == undefined) {
			prevpagehref = 'javascript:;" class="grey';
		} else {
			prevpagehref = prevpage;
		}
		if(nextpage == undefined) {
			nextpagehref = 'javascript:;" class="grey';
		} else {
			nextpagehref = nextpage;
		}

		var selector = '';
		if(lastpage) {
			selector += '<a id="select_a">';
			selector += '<select id="dumppage">';
			for(var i=1; i<=lastpage; i++) {
				selector += '<option value="'+i+'" '+ (i == curpage ? 'selected' : '') +'>第'+i+'页</option>';
			}
			selector += '</select>';
			selector += '<span>第'+curpage+'页</span>';
		}

		var pgobj = qSel('div.pg');
		pgobj.classList.remove('pg');
		pgobj.classList.add('page');
		pgobj.innerHTML = '<a href="'+ prevpagehref +'">上一页</a>'+ selector +'<a href="'+ nextpagehref +'">下一页</a>';
		qSel('#dumppage').addEventListener('change', function() {
			var href = (prevpage || nextpage);
			window.location.href = href.replace(/page=\d+/, 'page=' + this.value);
		});
	},
};

var scrolltop = {
	obj : null,
	init : function(obj) {
		scrolltop.obj = obj;
		var pageHeight = Math.max(document.body.scrollHeight, document.body.offsetHeight);
		var screenHeight = window.innerHeight;
		var scrollType = 'bottom';
		var scrollToPos = function() {
			if(scrollType == 'bottom') {
				window.scrollTo(0, pageHeight);
			} else {
				window.scrollTo(0, 0);
			}
			scrollfunc();
		};
		var scrollfunc = function() {
			var newType;
			if(document.documentElement.scrollTop >= 50) {
				newType = 'top';
			} else {
				newType = 'bottom';
			}
			if(newType != scrollType) {
				scrollType = newType;
				if(newType == 'top') {
					obj.classList.remove('bottom');
				} else {
					obj.classList.add('bottom');
				}
			}
		};
		if(pageHeight - screenHeight < 100) {
			obj.style.display = 'none';
		} else {
			obj.addEventListener('click', scrollToPos);
			document.addEventListener('scroll', scrollfunc);
			scrollfunc();
		}
	},
};

var img = {
	init : function(is_err_t) {
		var errhandle = this.errorhandle;
		$('img').on('load', function() {
			var obj = $(this);
			obj.attr('zsrc', obj.attr('src'));
			if(obj.width() < 5 && obj.height() < 10 && obj.css('display') != 'none') {
				return errhandle(obj, is_err_t);
			}
			obj.css('display', 'inline');
			obj.css('visibility', 'visible');
			if(obj.width() > window.innerWidth) {
				obj.css('width', window.innerWidth);
			}
			obj.parent().find('.loading').remove();
			obj.parent().find('.error_text').remove();
		})
		.on('error', function() {
			var obj = $(this);
			obj.attr('zsrc', obj.attr('src'));
			errhandle(obj, is_err_t);
		});
	},
	errorhandle : function(obj, is_err_t) {
		if(obj.attr('noerror') == 'true') {
			return;
		}
		obj.css('visibility', 'hidden');
		obj.css('display', 'none');
		var parentnode = obj.parent();
		parentnode.find('.loading').remove();
		parentnode.append('<div class="loading" style="background:url('+ IMGDIR +'/imageloading.gif) no-repeat center center;width:'+parentnode.width()+'px;height:'+parentnode.height()+'px"></div>');
		var loadnums = parseInt(obj.attr('load')) || 0;
		if(loadnums < 3) {
			obj.attr('src', obj.attr('zsrc'));
			obj.attr('load', ++loadnums);
			return false;
		}
		if(is_err_t) {
			var parentnode = obj.parent();
			parentnode.find('.loading').remove();
			parentnode.append('<div class="error_text">点击重新加载</div>');
			parentnode.find('.error_text').one('click', function() {
				obj.attr('load', 0).find('.error_text').remove();
				parentnode.append('<div class="loading" style="background:url('+ IMGDIR +'/imageloading.gif) no-repeat center center;width:'+parentnode.width()+'px;height:'+parentnode.height()+'px"></div>');
				obj.attr('src', obj.attr('zsrc'));
			});
		}
		return false;
	}
};

var POPMENU = new Object;
var popup = {
	init : function() {
		var $this = this;
		$('.popup').each(function(index, obj) {
			obj = $(obj);
			var pop = $(obj.attr('href'));
			if(pop && pop.attr('popup')) {
				pop.css({'display':'none'});
				obj.on('click', function(e) {
					$this.open(pop);
				});
			}
		});
		this.maskinit();
	},
	maskinit : function() {
		var $this = this;
		$('#mask').off().on('click', function() {
			$this.close();
		});
	},

	open : function(pop, type, url) {
		this.close();
		this.maskinit();
		if(typeof pop == 'string') {
			$('#ntcmsg').remove();
			if(type == 'alert') {
				pop = '<div class="tip"><dt>'+ pop +'</dt><dd><input class="button2" type="button" value="确定" onclick="popup.close();"></dd></div>'
			} else if(type == 'confirm') {
				pop = '<div class="tip"><dt>'+ pop +'</dt><dd><input class="redirect button2" type="button" value="确定" href="'+ url +'"><a href="javascript:;" onclick="popup.close();">取消</a></dd></div>'
			}
			$('body').append('<div id="ntcmsg" style="display:none;">'+ pop +'</div>');
			pop = $('#ntcmsg');
		}
		if(POPMENU[pop.attr('id')]) {
			$('#' + pop.attr('id') + '_popmenu').html(pop.html()).css({'height':pop.height()+'px', 'width':pop.width()+'px'});
		} else {
			pop.parent().append('<div class="dialogbox" id="'+ pop.attr('id') +'_popmenu" style="height:'+ pop.height() +'px;width:'+ pop.width() +'px;">'+ pop.html() +'</div>');
		}
		var popupobj = $('#' + pop.attr('id') + '_popmenu');
		var left = (window.innerWidth - popupobj.width()) / 2;
		var top = (document.documentElement.clientHeight - popupobj.height()) / 2;
		popupobj.css({'display':'block','position':'fixed','left':left,'top':top,'z-index':120,'opacity':1});
		$('#mask').css({'display':'block','width':'100%','height':'100%','position':'fixed','top':'0','left':'0','background':'black','opacity':'0.2','z-index':'100'});
		POPMENU[pop.attr('id')] = pop;
	},
	close : function() {
		$('#mask').css('display', 'none');
		$.each(POPMENU, function(index, obj) {
			$('#' + index + '_popmenu').css('display','none');
		});
	}
};

var dialog = {
	init : function() {
		$(document).on('click', '.dialog', function() {
			var obj = $(this);
			popup.open('<img src="' + IMGDIR + '/imageloading.gif">');
			$.ajax({
				type : 'GET',
				url : obj.attr('href') + '&inajax=1',
				dataType : 'xml'
			})
			.success(function(s) {
				popup.open(s.lastChild.firstChild.nodeValue);
				evalscript(s.lastChild.firstChild.nodeValue);
			})
			.error(function() {
				window.location.href = obj.attr('href');
				popup.close();
			});
			return false;
		});
	},

};

var formdialog = {
	init : function() {
		$(document).on('click', '.formdialog', function() {
			popup.open('<img src="' + IMGDIR + '/imageloading.gif">');
			var obj = $(this);
			var formobj = $(this.form);
			var isFormData = formobj.find("input[type='file']").length > 0;
			$.ajax({
				type:'POST',
				url:formobj.attr('action') + '&handlekey='+ formobj.attr('id') +'&inajax=1',
				data:isFormData ? new FormData(formobj[0]) : formobj.serialize(),
				dataType:'xml',
				processData:isFormData ? false : true,
				contentType:isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8'
			})
			.success(function(s) {
				popup.open(s.lastChild.firstChild.nodeValue);
				evalscript(s.lastChild.firstChild.nodeValue);
			})
			.error(function() {
				popup.open('表单提交异常，无法完成您的请求', 'alert');
			});
			return false;
		});
	}
};

var redirect = {
	init : function() {
		qSelA('.redirect').forEach(function (rd) {
			rd.addEventListener('click', function () {
				popup.close();
				window.location.href = this.href;
			});
		});
	}
};

var DISMENU = new Object;
var display = {
	init : function() {
		var $this = this;
		$('.display').each(function(index, obj) {
			obj = $(obj);
			var dis = $(obj.attr('href'));
			if(dis && dis.attr('display')) {
				dis.css({'display':'none'});
				dis.css({'z-index':'102'});
				DISMENU[dis.attr('id')] = dis;
				obj.on('click', function(e) {
					if(in_array(e.target.tagName, ['A', 'IMG', 'INPUT'])) return;
					$this.maskinit();
					if(dis.attr('display') == 'true') {
						dis.css('display', 'block');
						dis.attr('display', 'false');
						$('#mask').css({'display':'block','width':'100%','height':'100%','position':'fixed','top':'0','left':'0','background':'transparent','z-index':'100'});
					}
					return false;
				});
			}
		});
	},
	maskinit : function() {
		var $this = this;
		$('#mask').off().on('touchstart', function() {
			$this.hide();
		});
	},
	hide : function() {
		$('#mask').css('display', 'none');
		$.each(DISMENU, function(index, obj) {
			obj.css('display', 'none');
			obj.attr('display', 'true');
		});
	}
};

function getID(id) {
	return !id ? null : document.getElementById(id);
}

function qSel(sel) {
	return document.querySelector(sel);
}

function qSelA(sel) {
	return document.querySelectorAll(sel);
}

function mygetnativeevent(event) {

	while(event && typeof event.originalEvent !== "undefined") {
		event = event.originalEvent;
	}
	return event;
}

function evalscript(s) {
	if(s.indexOf('<script') == -1) return s;
	var p = /<script[^\>]*?>([^\x00]*?)<\/script>/ig;
	var arr = [];
	while(arr = p.exec(s)) {
		var p1 = /<script[^\>]*?src=\"([^\>]*?)\"[^\>]*?(reload=\"1\")?(?:charset=\"([\w\-]+?)\")?><\/script>/i;
		var arr1 = [];
		arr1 = p1.exec(arr[0]);
		if(arr1) {
			appendscript(arr1[1], '', arr1[2], arr1[3]);
		} else {
			p1 = /<script(.*?)>([^\x00]+?)<\/script>/i;
			arr1 = p1.exec(arr[0]);
			appendscript('', arr1[2], arr1[1].indexOf('reload=') != -1);
		}
	}
	return s;
}

var safescripts = {}, evalscripts = [];

function appendscript(src, text, reload, charset) {
	var id = hash(src + text);
	if(!reload && in_array(id, evalscripts)) return;
	if(reload && getID(id)) {
		getID(id).parentNode.removeChild(getID(id));
	}

	evalscripts.push(id);
	var scriptNode = document.createElement("script");
	scriptNode.type = "text/javascript";
	scriptNode.id = id;
	scriptNode.charset = charset ? charset : (!document.charset ? document.characterSet : document.charset);
	try {
		if(src) {
			scriptNode.src = src;
			scriptNode.onloadDone = false;
			scriptNode.onload = function () {
				scriptNode.onloadDone = true;
				JSLOADED[src] = 1;
			};
			scriptNode.onreadystatechange = function () {
				if((scriptNode.readyState == 'loaded' || scriptNode.readyState == 'complete') && !scriptNode.onloadDone) {
					scriptNode.onloadDone = true;
					JSLOADED[src] = 1;
				}
			};
		} else if(text){
			scriptNode.text = text;
		}
		document.getElementsByTagName('head')[0].appendChild(scriptNode);
	} catch(e) {}
}

function hash(string, length) {
	var length = length ? length : 32;
	var start = 0;
	var i = 0;
	var result = '';
	filllen = length - string.length % length;
	for(i = 0; i < filllen; i++){
		string += "0";
	}
	while(start < string.length) {
		result = stringxor(result, string.substr(start, length));
		start += length;
	}
	return result;
}

function stringxor(s1, s2) {
	var s = '';
	var hash = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	var max = Math.max(s1.length, s2.length);
	for(var i=0; i<max; i++) {
		var k = s1.charCodeAt(i) ^ s2.charCodeAt(i);
		s += hash.charAt(k % 52);
	}
	return s;
}

function in_array(needle, haystack) {
	if(typeof needle == 'string' || typeof needle == 'number') {
		for(var i in haystack) {
			if(haystack[i] == needle) {
					return true;
			}
		}
	}
	return false;
}

function isUndefined(variable) {
	return typeof variable == 'undefined' ? true : false;
}

function setcookie(cookieName, cookieValue, seconds, path, domain, secure) {
	if(cookieValue == '' || seconds < 0) {
		cookieValue = '';
		seconds = -2592000;
	}
	if(seconds) {
		var expires = new Date();
		expires.setTime(expires.getTime() + seconds * 1000);
	}
	domain = !domain ? cookiedomain : domain;
	path = !path ? cookiepath : path;
	document.cookie = escape(cookiepre + cookieName) + '=' + escape(cookieValue)
		+ (expires ? '; expires=' + expires.toGMTString() : '')
		+ (path ? '; path=' + path : '/')
		+ (domain ? '; domain=' + domain : '')
		+ (secure ? '; secure' : '');
}

function getcookie(name, nounescape) {
	name = cookiepre + name;
	var cookie_start = document.cookie.indexOf(name);
	var cookie_end = document.cookie.indexOf(";", cookie_start);
	if(cookie_start == -1) {
		return '';
	} else {
		var v = document.cookie.substring(cookie_start + name.length + 1, (cookie_end > cookie_start ? cookie_end : document.cookie.length));
		return !nounescape ? unescape(v) : v;
	}
}

function browserVersion(types) {
	var other = 1;
	for(i in types) {
		var v = types[i] ? types[i] : i;
		if(USERAGENT.indexOf(v) != -1) {
			var re = new RegExp(v + '(\\/|\\s|:)([\\d\\.]+)', 'ig');
			var matches = re.exec(USERAGENT);
			var ver = matches != null ? matches[2] : 0;
			other = ver !== 0 && v != 'mozilla' ? 0 : other;
		} else {
			var ver = 0;
		}
		eval('BROWSER.' + i + '= ver');
	}
	BROWSER.other = other;
}

function AC_FL_RunContent() {
	var str = '';
	var ret = AC_GetArgs(arguments, "clsid:d27cdb6e-ae6d-11cf-96b8-444553540000", "application/x-shockwave-flash");
	if(BROWSER.ie && !BROWSER.opera) {
		str += '<object ';
		for (var i in ret.objAttrs) {
			str += i + '="' + ret.objAttrs[i] + '" ';
		}
		str += '>';
		for (var i in ret.params) {
			str += '<param name="' + i + '" value="' + ret.params[i] + '" /> ';
		}
		str += '</object>';
	} else {
		str += '<embed ';
		for (var i in ret.embedAttrs) {
			str += i + '="' + ret.embedAttrs[i] + '" ';
		}
		str += '></embed>';
	}
	return str;
}

function AC_GetArgs(args, classid, mimeType) {
	var ret = new Object();
	ret.embedAttrs = new Object();
	ret.params = new Object();
	ret.objAttrs = new Object();
	for (var i = 0; i < args.length; i = i + 2){
		var currArg = args[i].toLowerCase();
		switch (currArg){
			case "classid":break;
			case "pluginspage":ret.embedAttrs[args[i]] = 'http://www.macromedia.com/go/getflashplayer';break;
			case "src":ret.embedAttrs[args[i]] = args[i+1];ret.params["movie"] = args[i+1];break;
			case "codebase":ret.objAttrs[args[i]] = 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0';break;
			case "onafterupdate":case "onbeforeupdate":case "onblur":case "oncellchange":case "onclick":case "ondblclick":case "ondrag":case "ondragend":
			case "ondragenter":case "ondragleave":case "ondragover":case "ondrop":case "onfinish":case "onfocus":case "onhelp":case "onmousedown":
			case "onmouseup":case "onmouseover":case "onmousemove":case "onmouseout":case "onkeypress":case "onkeydown":case "onkeyup":case "onload":
			case "onlosecapture":case "onpropertychange":case "onreadystatechange":case "onrowsdelete":case "onrowenter":case "onrowexit":case "onrowsinserted":case "onstart":
			case "onscroll":case "onbeforeeditfocus":case "onactivate":case "onbeforedeactivate":case "ondeactivate":case "type":
			case "id":ret.objAttrs[args[i]] = args[i+1];break;
			case "width":case "height":case "align":case "vspace": case "hspace":case "class":case "title":case "accesskey":case "name":
			case "tabindex":ret.embedAttrs[args[i]] = ret.objAttrs[args[i]] = args[i+1];break;
			default:ret.embedAttrs[args[i]] = ret.params[args[i]] = args[i+1];
		}
	}
	ret.objAttrs["classid"] = classid;
	if(mimeType) {
		ret.embedAttrs["type"] = mimeType;
	}
	return ret;
}

function appendstyle(url) {
	var link = document.createElement('link');
	link.type = 'text/css';
	link.rel = 'stylesheet';
	link.href = url;
	var head = document.getElementsByTagName('head')[0];
	head.appendChild(link);
}

function detectHtml5Support() {
	return document.createElement("Canvas").getContext;
}

function detectPlayer(randomid, ext, src, width, height) {
	var h5_support = new Array('aac', 'flac', 'mp3', 'm4a', 'wav', 'flv', 'mp4', 'm4v', '3gp', 'ogv', 'ogg', 'weba', 'webm');
	var trad_support = new Array('mp3', 'wma', 'mid', 'wav', 'ra', 'ram', 'rm', 'rmvb', 'swf', 'asf', 'asx', 'wmv', 'avi', 'mpg', 'mpeg', 'mov');
	if (in_array(ext, h5_support) && detectHtml5Support()) {
		html5Player(randomid, ext, src, width, height);
	} else if (in_array(ext, trad_support)) {
		tradionalPlayer(randomid, ext, src, width, height);
	} else {
		document.getElementById(randomid).style.width = '100%';
		document.getElementById(randomid).style.height = height + 'px';
	}
}

function tradionalPlayer(randomid, ext, src, width, height) {
	switch(ext) {
		case 'mp3':
		case 'wma':
		case 'mid':
		case 'wav':
			height = 64;
			html = '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="' + width + '" height="' + height + '"><param name="invokeURLs" value="0"><param name="autostart" value="0" /><param name="url" value="' + src + '" /><embed src="' + src + '" autostart="0" type="application/x-mplayer2" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		case 'ra':
		case 'ram':
			height = 32;
			html = '<object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="' + width + '" height="' + height + '"><param name="autostart" value="0" /><param name="src" value="' + src + '" /><param name="controls" value="controlpanel" /><param name="console" value="' + randomid + '_" /><embed src="' + src + '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="ControlPanel" console="' + randomid + '_" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		case 'rm':
		case 'rmvb':
			html = '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" width="' + width + '" height="' + height + '"><param name="autostart" value="0" /><param name="src" value="' + src + '" /><param name="controls" value="imagewindow" /><param name="console" value="' + randomid + '_" /><embed src="' + src + '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="imagewindow" console="' + randomid + '_" width="' + width + '" height="' + height + '"></embed></object><br /><object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="' + width + '" height="32"><param name="src" value="' + src +'" /><param name="controls" value="controlpanel" /><param name="console" value="' + randomid + '_" /><embed src="' + src + '" autostart="0" type="audio/x-pn-realaudio-plugin" controls="controlpanel" console="' + randomid + '_" width="' + width + '" height="32"></embed></object>';
			break;
		case 'swf':
			html = AC_FL_RunContent('width', width, 'height', height, 'allowNetworking', 'internal', 'allowScriptAccess', 'never', 'src', encodeURI(src), 'quality', 'high', 'bgcolor', '#ffffff', 'wmode', 'transparent', 'allowfullscreen', 'true');
			break;
		case 'asf':
		case 'asx':
		case 'wmv':
		case 'avi':
		case 'mpg':
		case 'mpeg':
			html = '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="' + width + '" height="' + height + '"><param name="invokeURLs" value="0"><param name="autostart" value="0" /><param name="url" value="' + src + '" /><embed src="' + src + '" autostart="0" type="application/x-mplayer2" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		case 'mov':
			html = '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="' + width + '" height="' + height + '"><param name="autostart" value="false" /><param name="src" value="' + src + '" /><embed src="' + src + '" autostart="false" type="video/quicktime" controller="true" width="' + width + '" height="' + height + '"></embed></object>';
			break;
		default:
			break;
	}
	document.getElementById(randomid).style.width = '100%';
	document.getElementById(randomid).style.height = height + 'px';
	document.getElementById(randomid + '_container').innerHTML = html;
}

function html5Player(randomid, ext, src, width, height) {
	switch (ext) {
		case 'aac':
		case 'flac':
		case 'mp3':
		case 'm4a':
		case 'wav':
		case 'ogg':
			height = 66;
			if(!HTML5PLAYER['apload']) {
				appendstyle(STATICURL + 'js/player/aplayer.min.css');
				appendscript(STATICURL + 'js/player/aplayer.min.js');
				HTML5PLAYER['apload'] = 1;
			}
			html5APlayer(randomid, ext, src, width, height);
			break;
		case 'flv':
			if(!HTML5PLAYER['flvload']) {
				appendscript(STATICURL + 'js/player/flv.min.js');
				HTML5PLAYER['flvload'] = 1;
			}
		case 'mp4':
		case 'm4v':
		case '3gp':
		case 'ogv':
		case 'webm':
			if(!HTML5PLAYER['dpload']) {
				appendstyle(STATICURL + 'js/player/dplayer.min.css');
				appendscript(STATICURL + 'js/player/dplayer.min.js');
				HTML5PLAYER['dpload'] = 1;
			}
			html5DPlayer(randomid, ext, src, width, height);
			break;
		default:
			break;
	}
	document.getElementById(randomid).style.width = '100%';
}

function html5APlayer(randomid, ext, src, width, height) {
	if (JSLOADED[STATICURL + 'js/player/aplayer.min.js']) {
		window[randomid] = new APlayer({
			container: document.getElementById(randomid + '_container'),
			mini: false,
			autoplay: false,
			loop: 'all',
			preload: 'none',
			volume: 1,
			mutex: true,
			listFolded: true,
			audio: [{
				name: ' ',
				artist: ' ',
				url: src,
			}]
		});
	} else {
		setTimeout(function () {
			html5APlayer(randomid, ext, src, width, height);
		}, 50);
	}
}

function html5DPlayer(randomid, ext, src, width, height) {
	if (JSLOADED[STATICURL + 'js/player/dplayer.min.js'] && (ext != 'flv' || JSLOADED[STATICURL + 'js/player/flv.min.js'])) {
		window[randomid] = new DPlayer({
			container: document.getElementById(randomid + '_container'),
			autoplay: false,
			loop: true,
			screenshot: false,
			hotkey: true,
			preload: 'none',
			volume: 1,
			mutex: true,
			listFolded: true,
			video: {
				url: src,
			}
		});
	} else {
		setTimeout(function () {
			html5DPlayer(randomid, ext, src, width, height);
		}, 50);
	}
}

$(document).ready(function() {

	if(qSel('div.pg')) {
		page.converthtml();
	}
	if(qSel('.scrolltop')) {
		scrolltop.init(qSel('.scrolltop'));
	}
	if($('img').length > 0) {
		img.init(1);
	}
	if($('.popup').length > 0) {
		popup.init();
	}
	if($('.display').length > 0) {
		display.init();
	}
	dialog.init();
	formdialog.init();
	redirect.init();
});

function ajaxget(url, showid, waitid, loading, display, recall) {
	var url = url + '&inajax=1&ajaxtarget=' + showid;
	$.ajax({
		type : 'GET',
		url : url,
		dataType : 'xml',
	}).success(function(s) {
		$('#'+showid).html(s.lastChild.firstChild.nodeValue);
		$("[ajaxtarget]").off('click').on('click', function(e) {
			var id = $(this);
			ajaxget(id.attr('href'), id.attr('ajaxtarget'));
			return false;
		});
	});
	return false;
}
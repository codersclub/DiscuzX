function init_darkmode() {
	var dmcookie = getcookie('darkmode');
	var dmdark = 0, dmauto = 1;
	document.querySelector('.darkmode').addEventListener('click', toggledarkmode);
	if (dmcookie && dmcookie.indexOf('a') == -1) {
		dmauto = 0;
		if (dmcookie.indexOf('d') != -1) {
			dmdark = 1;
		}
		switchdmvalue(dmdark, dmauto);
	} else {
		var colormedia = window.matchMedia('(prefers-color-scheme: dark)');
		switchdmvalue(colormedia.matches, dmauto);
		colormedia.addEventListener('change', function () {
			var dmlcookie = getcookie('darkmode');
			if (dmlcookie && dmlcookie.indexOf('a') != -1) {
				switchdmvalue(this.matches, 1);
			}
		});
	}
}
function toggledarkmode() {
	var dmcookie = getcookie('darkmode');
	var dmdark = 0, dmauto = 1;
	var colormedia = window.matchMedia('(prefers-color-scheme: dark)');
	if (dmcookie && dmcookie.indexOf('a') == -1) {
		dmauto = 0;
		if (dmcookie.indexOf('d') != -1) {
			dmdark = 1;
		}
	} else {
		dmdark = colormedia.matches ? 1 : 0;
	}
	if (dmauto) {
		dmauto = dmauto ? 0 : 1;
		dmdark = dmdark ? 0 : 1;
	} else if (colormedia.matches == dmdark) {
		dmauto = 1;
	} else {
		dmdark = dmdark ? 0 : 1;
	}
	switchdmvalue(dmdark, dmauto);
}
function switchdmvalue(ifdark, ifauto) {
	var dmcookie = '';
	var dmmeta = '';
	if (ifdark) {
		document.body.classList.add('st-d');
		document.body.classList.remove('st-l');
		dmcookie = 'd';
		dmmeta = 'dark';
	} else {
		document.body.classList.add('st-l');
		document.body.classList.remove('st-d');
		dmcookie = 'l';
		dmmeta = 'light';
	}
	if (ifauto) {
		document.body.classList.add('st-a');
		dmcookie += 'a';
		dmmeta = 'light dark';
	} else {
		document.body.classList.remove('st-a');
	}
	let iframe = document.getElementById('main');
	iframe.onload = function(){
		switchframedmvalue(ifdark, ifauto)
	}
	switchframedmvalue(ifdark, ifauto)
	console.log(dmcookie);
	if (getcookie('darkmode') != dmcookie) {
		setcookie('darkmode', dmcookie);
	}
	if (document.querySelector('meta[name="color-scheme"]').content != dmmeta) {
		document.querySelector('meta[name="color-scheme"]').content = dmmeta;
	}
}
function switchframedmvalue(ifdark, ifauto) {
		let iframe = document.getElementById('main');
		if (ifdark) {
			iframe.contentWindow.document.body.classList.add('st-d');
			iframe.contentWindow.document.body.classList.remove('st-l');
		} else {
			iframe.contentWindow.document.body.classList.add('st-l');
			iframe.contentWindow.document.body.classList.remove('st-d');
		}
		if (ifauto) {
			iframe.contentWindow.document.body.classList.add('st-a');
		} else {
			iframe.contentWindow.document.body.classList.remove('st-a');
		}
}

(function () {
	var prevnav = prevtab = menunav = navt = navkey = headerST = null;
	function switchnav(key, nolocation = false, switchheader = true) {
		if (!key || !$('header_' + key)) {
			return;
		}
		if (prevnav && $('header_' + prevnav) && key != 'cloudaddons' && key != 'uc') {
			document.querySelectorAll('#topmenu button').forEach(function (nav) {
				navkey = nav.id.substring(7);
				if (navkey  && $('header_' + navkey)) {
					if (switchheader) {
						$('header_' + navkey).className = '';
					}
					$('lm_' + navkey).className = '';
				}
			});
		}
		href = $('lm_' + key).childNodes[1].childNodes[0].childNodes[0].href;
		if (key == 'cloudaddons' || key == 'uc') {
			if (!nolocation) {
				window.open(href);
				doane();
			}
		} else {
			if (prevnav == key && (getcookie('admincp_leftlayout') || parseInt(document.documentElement.clientWidth) < 1200)) {
				$('header_' + prevnav).className = '';
				$('lm_' + prevnav).className = '';
				prevnav = null;
			} else {
				$('lm_' + key).className = 'active';
				if (switchheader) {
					$('header_' + key).className = 'active';
					prevnav = key;
				}
				if (!nolocation) {
					switchtab($('lm_' + key).childNodes[1].childNodes[0].childNodes[0]);
					parent.main.location = href;
				}
			}
		}
	}
	function switchtab(key) {
		if (!key || !key.href) {
			return;
		}
		if(prevtab) {
			prevtab.className = '';
		}
		key.className = 'active';
		prevtab = key;
		$('navcontainer').classList.remove('show');
	}
	function openinnewwindow(obj) {
		var href = obj.parentNode.href;
		if(obj.parentNode.href.indexOf(admincpfilename + '?') != -1) {
			href += '&frames=yes';
		}
		window.open(href);
		doane();
	}
	document.querySelectorAll('#leftmenu > li > a').forEach(function (nav) {
		nav.addEventListener('click', function () {
			nolocation = true;
			id = this.id.substring(7);
			if (id == 'cloudaddons' || id == 'uc') {
				nolocation = false;
			}
			switchnav(id, nolocation);
		});
	});
	document.querySelectorAll('#topmenu > li > a').forEach(function (nav) {
		nav.addEventListener('click', function () {
			switchnav(this.id.substring(7));
		});
	});
	document.querySelectorAll('#topmenu button').forEach(function (nav) {
		nav.addEventListener('click', function () {
			switchnav(this.id.substring(7));
		});
		nav.addEventListener('mouseover', function () {
			id = this.id.substring(7);
			headerST = setTimeout(function () {
				switchnav(id, true, false);
			}, 1000);
		});
		nav.addEventListener('mouseout', function () {
			clearTimeout(headerST);
		});
	});
	document.querySelectorAll('nav ul ul a').forEach(function (tab) {
		tab.addEventListener('click', function () {
			switchtab(this);
		});
	});
	document.querySelectorAll('nav ul ul a > em').forEach(function (tabem) {
		tabem.addEventListener('click', function () {
			openinnewwindow(this);
		});
	});
	switchnav(typeof defaultNav != 'undefined' ? defaultNav : 'index', 1);
	switchtab(document.querySelector('nav ul li.active ul a.active') != null ? document.querySelector('nav ul li.active ul a.active') : document.querySelector('nav ul ul a'));
	$('cpsetting').addEventListener('click', function(){
		$('bdcontainer').classList.toggle('oldlayout');
		setcookie('admincp_leftlayout', 1, getcookie('admincp_leftlayout') ? -2592000 : 2592000);
	});
	document.querySelector('#frameuinfo > img').addEventListener('click', function(){
		document.querySelector('.mainhd').classList.toggle('toggle');
	});
	$('navbtn').addEventListener('click', function(){
		$('navcontainer').classList.add('show');
	});
})()
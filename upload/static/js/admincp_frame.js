(function () {
	var prevnav = prevtab = menunav = null;
	function switchnav(key) {
		if (!key || !$('header_' + key)) {
			return;
		}
		if (prevnav == key) {
			$('header_' + prevnav).className = '';
			$('lm_' + prevnav).className = '';
			prevnav = '';
			return;
		}
		if (prevnav && $('header_' + prevnav)) {
			$('header_' + prevnav).className = '';
			$('lm_' + prevnav).className = '';
		}
		$('header_' + key).className = 'active';
		$('lm_' + key).className = 'active';
		if (key != 'cloudaddons' && key != 'uc') {
			href = $('lm_' + key).childNodes[1].childNodes[0].childNodes[0].href;
			parent.main.location = href;
		}
		prevnav = key;
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
	document.querySelectorAll('nav > ul > li > a').forEach(function (nav) {
		nav.addEventListener('click', function () {
			switchnav(this.id.substring(7));
		});
	});
	document.querySelectorAll('#topmenu button').forEach(function (nav) {
		nav.addEventListener('click', function () {
			switchnav(this.id.substring(7));
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
	switchnav('index');
	switchtab(document.querySelector('nav ul ul a'));
	$('cpsetting').addEventListener('click', function(){
		$('bdcontainer').classList.toggle('oldlayout');
	});
	document.querySelector('#frameuinfo > img').addEventListener('click', function(){
		document.querySelector('.mainhd').classList.toggle('toggle');
	});
	$('navbtn').addEventListener('click', function(){
		$('navcontainer').classList.add('show');
	});
})()
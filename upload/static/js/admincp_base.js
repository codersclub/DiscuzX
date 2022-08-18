/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms
*/

function run_toggle(target, styles, source) {
	var rmstyle = styles.shift();
	if (!source) {
		source = target;
	}
	if (rmstyle) {
		if (typeof rmstyle == 'string') {
			target.classList.remove(rmstyle);
		} else {
			for (var i in rmstyle) {
				target.classList.remove(rmstyle[i]);
			}
		}
	}
	if (styles[0]) {
		if (typeof styles[0] == 'string') {
			target.classList.add(styles[0]);
		} else {
			for (var i in styles[0]) {
				target.classList.add(styles[0][i]);
			}
		}
		if (styles.length > 1) {
			function nextstep() {
				source.removeEventListener('transitionend', nextstep);
				run_toggle(target, styles, source);
			}
			source.addEventListener('transitionend', nextstep);
		}
	}
}
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
	} console.log(dmcookie);
	if (getcookie('darkmode') != dmcookie) {
		setcookie('darkmode', dmcookie);
	}
	if (document.querySelector('meta[name="color-scheme"]').content != dmmeta) {
		document.querySelector('meta[name="color-scheme"]').content = dmmeta;
	}
}
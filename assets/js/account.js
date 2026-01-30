(function(){'use strict';function $$(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s));}
function dismissAll(){ $$('[data-wcu-consent-banner]').forEach(e=>e.style.display='none');}
function bind(){ $$('[data-wcu-dismiss-consent]').forEach(btn=>{
	btn.addEventListener('click',function(){
		if(!window.wcuAccount||!wcuAccount.ajax_url){dismissAll();return;}
		var x=new XMLHttpRequest();x.open('POST',wcuAccount.ajax_url,true);
		x.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		x.onreadystatechange=function(){if(x.readyState!==4)return;dismissAll();};
		x.send('action=wcu_dismiss_consent_notice&nonce='+(encodeURIComponent(wcuAccount.nonce||'')));
	});
});}
document.addEventListener('DOMContentLoaded',bind);}());
(function(){'use strict';function setDisabled(t,d){if(!t)return;Array.prototype.forEach.call(t.querySelectorAll('input,select,textarea'),el=>{el.disabled=d;});}
function applyToggle(cb){if(!cb)return;var sel=cb.getAttribute('data-target');if(!sel)return;var target=document.querySelector(sel);if(!target)return;setDisabled(target,!cb.checked);}
function bindOne(cb){if(!cb||cb.__wcuBound)return;['change','input','click'].forEach(evt=>cb.addEventListener(evt,()=>applyToggle(cb),true));cb.__wcuBound=true;applyToggle(cb);}
function bindAll(){Array.prototype.forEach.call(document.querySelectorAll('[data-wcu-toggle]'),bindOne);}
function observe(){if(!('MutationObserver'in window))return;new MutationObserver(bindAll).observe(document.documentElement,{childList:true,subtree:true});}
function init(){bindAll();observe();}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();}());
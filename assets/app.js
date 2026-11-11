(()=>{'use strict';
const html=document.documentElement;
const stored=localStorage.getItem('craft-theme');
html.dataset.theme=stored==='light'?'light':'dark';
const theme=document.getElementById('theme-toggle');
if(theme)theme.addEventListener('click',()=>{const t=html.dataset.theme==='dark'?'light':'dark';html.dataset.theme=t;localStorage.setItem('craft-theme',t);});
const sidebar=document.getElementById('sidebar');const overlay=document.getElementById('sidebar-overlay');
const toggleSidebar=()=>{sidebar.classList.toggle('open');overlay.classList.toggle('visible')};
document.getElementById('menu-toggle')?.addEventListener('click',toggleSidebar);overlay?.addEventListener('click',toggleSidebar);
const q=document.getElementById('tool-search'),filters=[...document.querySelectorAll('.filter')],cards=[...document.querySelectorAll('.tool-card')];
let category='Todas';
function updateCards(){if(!cards.length)return;let visible=0;const query=(q?.value||'').trim().toLocaleLowerCase('pt-BR');cards.forEach(card=>{const match=(category==='Todas'||card.dataset.category===category)&&card.dataset.title.toLocaleLowerCase('pt-BR').includes(query);card.hidden=!match;if(match)visible++;});document.getElementById('no-results').hidden=visible>0;}
q?.addEventListener('input',updateCards);
filters.forEach(f=>f.addEventListener('click',()=>{category=f.dataset.cat;filters.forEach(b=>b.classList.toggle('active',b===f));updateCards()}));
const fullscreen=document.getElementById('app-fullscreen');
fullscreen?.addEventListener('click',()=>{const frame=document.getElementById('frame-container');if(!frame)return;if(document.fullscreenElement)document.exitFullscreen?.();else frame.requestFullscreen?.();});
})();

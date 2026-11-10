(()=>{'use strict';
document.querySelectorAll('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>el.form?.requestSubmit()));
document.querySelectorAll('[data-analyte]').forEach(panel=>{
 const select=panel.querySelector('[data-analyte-select]');
 const refresh=()=>{
  panel.querySelectorAll('[data-analyte-field]').forEach(el=>el.disabled=!select.checked);
  panel.classList.toggle('is-selected',select.checked);
  const count=document.querySelector('[data-selected-count]');
  if(count)count.textContent=String(document.querySelectorAll('[data-analyte-select]:checked').length);
 };
 select?.addEventListener('change',()=>{panel.open=select.checked;refresh()});refresh();
});
document.querySelectorAll('[data-catalog-search]').forEach(el=>el.addEventListener('input',()=>{
 const q=el.value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
 document.querySelectorAll('[data-analyte]').forEach(p=>{
  const text=(p.dataset.search||'').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
  p.hidden=!text.includes(q);
 });
}));
document.querySelectorAll('[data-chart-point]').forEach(point=>{
 const show=()=>{const target=point.closest('.chart-card')?.querySelector('[data-point-details]');if(target)target.textContent=point.dataset.chartPoint};
 ['click','focus','mouseenter'].forEach(event=>point.addEventListener(event,show));
});
document.querySelectorAll('[data-print]').forEach(el=>el.addEventListener('click',()=>window.print()));
document.getElementById('chart-configuration')?.addEventListener('change',()=>{const analyte=document.getElementById('chart-analyte');if(analyte)analyte.value=''});
document.querySelectorAll('[data-run-kind]').forEach(el=>{
 const change=()=>{
  const date=document.getElementById('measured-at'),notes=document.getElementById('run-notes');
  if(!date||!el.dataset.parentMeasured)return;
  const correction=el.value==='correction';date.readOnly=correction;
  if(correction)date.value=el.dataset.parentMeasured;
  if(notes)notes.required=correction;
 };
 el.addEventListener('change',change);change();
});
document.querySelectorAll('form[data-save-form]').forEach(form=>form.addEventListener('submit',event=>{
 if(!form.checkValidity())return;
 const button=event.submitter;
 if(button){button.dataset.originalLabel=button.textContent;button.setAttribute('aria-busy','true');setTimeout(()=>{button.disabled=true;button.textContent='Guardando…'},0)}
}));
})();

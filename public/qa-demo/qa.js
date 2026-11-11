(()=>{'use strict';
const all=(selector,root=document)=>Array.from(root.querySelectorAll(selector));
const refreshAnalyte=panel=>{
 const selected=panel.querySelector('[data-analyte-select]')?.checked ?? false;
 all('[data-analyte-field]',panel).forEach(el=>el.disabled=!selected);
 panel.classList.toggle('is-selected',selected);
 const count=document.querySelector('[data-selected-count]');
 if(count)count.textContent=String(document.querySelectorAll('[data-analyte-select]:checked').length);
};
all('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>el.form?.requestSubmit()));
all('[data-analyte]').forEach(panel=>{
 const select=panel.querySelector('[data-analyte-select]');
 select?.addEventListener('change',()=>{panel.open=select.checked;refreshAnalyte(panel)});
 refreshAnalyte(panel);
});
all('[data-catalog-search]').forEach(el=>el.addEventListener('input',()=>{
 const clean=value=>value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
 const q=clean(el.value);
 all('[data-analyte]').forEach(panel=>panel.hidden=!clean(panel.dataset.search||'').includes(q));
}));
const levelList=document.querySelector('[data-level-list]');
if(levelList){
 const targetBodies=all('[data-targets-body]');
 const labelFor=key=>all('[data-config-level]',levelList).find(card=>card.dataset.configLevel===key)?.querySelector('[data-level-label]')?.value || 'Nivel';
 const refreshLevels=()=>{
  const cards=all('[data-config-level]',levelList);
  const empty=document.querySelector('[data-level-empty]');if(empty)empty.hidden=cards.length>0;
  cards.forEach(card=>{
   const key=card.dataset.configLevel,label=labelFor(key);
   card.querySelector('[data-level-heading]').textContent=label;
   all('[data-target-level]').filter(row=>row.dataset.targetLevel===key).forEach(row=>row.querySelector('[data-target-label]').textContent=label);
  });
 };
 const bindCard=card=>{
  card.querySelector('[data-level-label]')?.addEventListener('input',refreshLevels);
  card.querySelector('[data-remove-level]')?.addEventListener('click',()=>{
   const key=card.dataset.configLevel;
   all('[data-target-level]').filter(row=>row.dataset.targetLevel===key).forEach(row=>row.remove());
   card.remove();refreshLevels();
  });
 };
 all('[data-config-level]',levelList).forEach(bindCard);
 document.querySelector('[data-add-level]')?.addEventListener('click',()=>{
  const error=document.querySelector('[data-level-error]');
  if(all('[data-config-level]',levelList).length>=50){if(error){error.hidden=false;error.textContent='Se pueden configurar hasta 50 niveles.'}return}
  if(error)error.hidden=true;
  let index=1;while(document.getElementById('level_'+index+'-label'))index++;
  const key='level_'+index,template=document.getElementById('level-card-template');
  const holder=document.createElement('template');holder.innerHTML=template.innerHTML.replaceAll('__LEVEL__',key);
  const card=holder.content.firstElementChild;
  card.querySelector('[data-level-label]').value='Nivel '+(all('[data-config-level]',levelList).length+1);
  levelList.append(card);bindCard(card);
  targetBodies.forEach(body=>{
   const panel=body.closest('[data-analyte]'),i=panel.dataset.analyteIndex;
   const row=document.createElement('tr');row.dataset.targetLevel=key;
   const heading=document.createElement('th');heading.scope='row';heading.dataset.targetLabel='';row.append(heading);
   [['target','Valor esperado'],['min','Mínimo'],['max','Máximo']].forEach(([field,label])=>{
    const td=document.createElement('td'),input=document.createElement('input');
    input.name='analytes['+i+'][targets]['+key+']['+field+']';
    input.id='target-new-'+i+'-'+key+'-'+field;input.inputMode='decimal';input.maxLength=30;input.required=true;input.dataset.analyteField='';
    const caption=document.createElement('label');caption.htmlFor=input.id;caption.className='sr-only';caption.textContent=label;
    td.append(caption,input);row.append(td);
   });
   body.append(row);refreshAnalyte(panel);
  });
  refreshLevels();card.querySelector('[data-level-label]').focus();
 });
 refreshLevels();
}
all('[data-chart-point]').forEach(point=>{
 const show=()=>{const target=point.closest('.chart-card')?.querySelector('[data-point-details]');if(target)target.textContent=point.dataset.chartPoint};
 ['click','focus','mouseenter'].forEach(event=>point.addEventListener(event,show));
});
all('[data-print]').forEach(el=>el.addEventListener('click',()=>window.print()));
document.getElementById('chart-configuration')?.addEventListener('change',()=>{const analyte=document.getElementById('chart-analyte');if(analyte)analyte.value=''});
all('[data-area-selector]').forEach(form=>{
 const equipment=form.querySelector('[data-area-equipment]'),configuration=form.querySelector('[data-area-configuration]');
 const filter=()=>{
  all('option[data-equipment]',configuration).forEach(option=>{
   const hidden=!!equipment.value && option.dataset.equipment!==equipment.value;
   option.hidden=hidden;option.disabled=hidden;
   if(hidden&&option.selected)configuration.value='';
  });
 };
 equipment.addEventListener('change',()=>{configuration.value='';filter()});filter();
});
all('[data-auto-clock]').forEach(input=>{
 const formatter=new Intl.DateTimeFormat('en-CA',{timeZone:'America/El_Salvador',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23'});
 const tick=()=>{const parts=Object.fromEntries(formatter.formatToParts(new Date()).map(part=>[part.type,part.value]));input.value=parts.year+'-'+parts.month+'-'+parts.day+'T'+parts.hour+':'+parts.minute+':'+parts.second};
 tick();setInterval(tick,1000);
});
const showFormError=(form,message)=>{
 let notice=form.querySelector('[data-payload-error]');
 if(!notice){notice=document.createElement('div');notice.dataset.payloadError='';notice.className='notice error';notice.role='alert';notice.tabIndex=-1;form.prepend(notice)}
 notice.textContent=message;notice.focus();
};
all('form[data-save-form]').forEach(form=>form.addEventListener('submit',event=>{
 if(!form.checkValidity())return;
 const config=form.hasAttribute('data-config-form'),run=form.hasAttribute('data-run-form');
 if(config||run){
  const fields=new FormData(form),data=config?{levels:{},analytes:{}}:{values:{}};
  for(const [name,value] of fields){
   let match;
   if(config && (match=name.match(/^levels\[([^\]]+)\]\[([^\]]+)\]$/))){(data.levels[match[1]]??={})[match[2]]=value}
   else if(config && (match=name.match(/^analytes\[([^\]]+)\]\[(catalog_id|method|unit)\]$/))){(data.analytes[match[1]]??={})[match[2]]=value}
   else if(config && (match=name.match(/^analytes\[([^\]]+)\]\[targets\]\[([^\]]+)\]\[(target|min|max)\]$/))){const item=data.analytes[match[1]]??={};item.targets??={};(item.targets[match[2]]??={})[match[3]]=value}
   else if(run && (match=name.match(/^values\[([^\]]+)\]\[([^\]]+)\]$/))){(data.values[match[1]]??={})[match[2]]=value}
  }
  if(config && !Object.keys(data.levels).length){event.preventDefault();showFormError(form,'Agrega al menos un nivel de control.');return}
  if(config && !Object.keys(data.analytes).length){event.preventDefault();showFormError(form,'Selecciona al menos una prueba.');return}
  const payload=JSON.stringify(data);
  if(new Blob([payload]).size>400000){event.preventDefault();showFormError(form,'El formulario contiene muchos datos. Reduce la cantidad de pruebas o niveles.');return}
  const hidden=document.createElement('input');hidden.type='hidden';hidden.name=config?'config_payload':'run_payload';hidden.value=payload;form.append(hidden);
  all('input,select,textarea',form).filter(el=>config?/^(levels|analytes)\[/.test(el.name):/^values\[/.test(el.name)).forEach(el=>el.disabled=true);
 }
 const button=event.submitter;
 if(button){button.dataset.originalLabel=button.textContent;button.setAttribute('aria-busy','true');setTimeout(()=>{button.disabled=true;button.textContent='Guardando…'},0)}
}));
})();

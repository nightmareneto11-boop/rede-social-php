// Envia fotos/vídeos direto do navegador para o Cloudinary (não passa pelo teu servidor).
(function(){
  var MAX_IMG=10*1024*1024, MAX_VID=100*1024*1024; // limites do plano grátis do Cloudinary
  var csrf=(document.querySelector('meta[name=csrf]')||{}).content||'';

  function sign(){
    return fetch('sign.php',{method:'POST',headers:{'X-CSRF':csrf}}).then(function(r){
      return r.json().catch(function(){return {};}).then(function(j){
        if(!r.ok)throw new Error(j.error||'Envio indisponível.');
        return j;
      });
    });
  }

  function send(file,onProgress){
    return sign().then(function(s){
      return new Promise(function(resolve,reject){
        var f=new FormData();
        f.append('file',file);
        f.append('api_key',s.api_key);
        f.append('timestamp',s.timestamp);
        f.append('signature',s.signature);
        f.append('folder',s.folder);
        f.append('allowed_formats',s.allowed_formats);
        var x=new XMLHttpRequest();
        x.open('POST','https://api.cloudinary.com/v1_1/'+encodeURIComponent(s.cloud_name)+'/auto/upload');
        x.upload.onprogress=function(e){if(e.lengthComputable)onProgress(Math.round(e.loaded*100/e.total));};
        x.onload=function(){
          var j={};try{j=JSON.parse(x.responseText);}catch(_){}
          if(x.status===200&&j.secure_url)resolve(j);
          else reject(new Error((j.error&&j.error.message)||'Falha no envio.'));
        };
        x.onerror=function(){reject(new Error('Sem ligação à internet.'));};
        x.send(f);
      });
    });
  }

  function busy(form,on){
    form.querySelectorAll('button:not(.link)').forEach(function(b){b.disabled=on;});
  }

  document.addEventListener('change',function(ev){
    var inp=ev.target;
    if(!inp.matches||!inp.matches('input[type=file][data-url]'))return;
    var file=inp.files&&inp.files[0]; if(!file)return;
    var box=inp.closest('.up'), form=inp.closest('form');
    var st=box.querySelector('.ustatus'), pv=box.querySelector('.upreview');
    var urlIn=box.querySelector('input[name="'+inp.dataset.url+'"]');
    var typeIn=inp.dataset.type?box.querySelector('input[name="'+inp.dataset.type+'"]'):null;
    var isVid=file.type.indexOf('video/')===0, isImg=file.type.indexOf('image/')===0;

    if(!isImg&&!(isVid&&inp.dataset.kind!=='image')){st.textContent='Escolhe '+(inp.dataset.kind==='image'?'uma foto.':'uma foto ou um vídeo.');inp.value='';return;}
    if((isImg&&file.size>MAX_IMG)||(isVid&&file.size>MAX_VID)){st.textContent='Ficheiro grande demais (fotos até 10 MB, vídeos até 100 MB).';inp.value='';return;}

    // pré-visualização local, instantânea
    pv.innerHTML='';
    var url=URL.createObjectURL(file), el=document.createElement(isVid?'video':'img');
    el.src=url; if(isVid){el.controls=true;el.muted=true;} pv.appendChild(el);

    urlIn.value=''; if(typeIn)typeIn.value='';
    busy(form,true); st.textContent='A enviar… 0%';
    send(file,function(p){st.textContent='A enviar… '+p+'%';}).then(function(j){
      urlIn.value=j.secure_url; if(typeIn)typeIn.value=j.resource_type; // 'image' ou 'video'
      st.textContent='Pronto ✓';
    }).catch(function(err){
      st.textContent=err.message; pv.innerHTML=''; inp.value='';
    }).then(function(){busy(form,false);});
  });
})();

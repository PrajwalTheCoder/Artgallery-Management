// Minimal JS for classic layout
const addForm = document.getElementById('addForm');
const searchForm = document.getElementById('searchForm');
const showAllBtn = document.getElementById('showAllBtn');

if (addForm) {
  addForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(addForm);
    const body = new URLSearchParams();
    body.set('action', 'json_add');
    for (const [k, v] of data.entries()) body.set(k, v);
    try {
      const res = await fetch('/cgi-bin/directory.exe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: body.toString()
      });
      if (!res.ok) {
        // Fallback to normal CGI submit in a new tab
        addForm.setAttribute('target','_blank');
        addForm.submit();
        addForm.removeAttribute('target');
        return;
      }
      const js = await res.json().catch(()=>null);
      if (js && js.ok) {
        alert('Contact added');
        addForm.reset();
      } else {
        // Show backend error or fallback to CGI page rendering
        if (js && js.error) alert(js.error);
        else {
          addForm.setAttribute('target','_blank');
          addForm.submit();
          addForm.removeAttribute('target');
        }
      }
    } catch (err) {
      // Network/fetch blocked — fallback
      addForm.setAttribute('target','_blank');
      addForm.submit();
      addForm.removeAttribute('target');
    }
  });
}

if (searchForm) {
  searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const q = document.getElementById('q')?.value || '';
    const url = '/cgi-bin/directory.exe?action=search&q=' + encodeURIComponent(q);
    window.open(url, '_blank');
  });
}

if (showAllBtn) {
  showAllBtn.addEventListener('click', () => {
    window.open('/cgi-bin/directory.exe', '_blank');
  });
}

function toggleTheme() {
  const html = document.documentElement;
  const tema = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', tema);
  localStorage.setItem('tema', tema);
}

const temaGuardado = localStorage.getItem('tema');
if (temaGuardado) document.documentElement.setAttribute('data-theme', temaGuardado);
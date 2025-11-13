window.addEventListener('storage', (e) => {
  if (e.key === 'token') {
    location.reload();
  }
});
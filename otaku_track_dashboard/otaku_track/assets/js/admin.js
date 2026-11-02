// Konfirmasi sebelum aksi yang menghapus data: <form data-confirm="Pesan konfirmasi">
document.addEventListener('submit', (event) => {
  const form = event.target.closest('form[data-confirm]');
  if (form && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});

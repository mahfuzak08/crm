document.addEventListener('click', function (e) {
  if (e.target.closest('[data-print]')) window.print();
  if (e.target.closest('[data-back]')) { e.preventDefault(); history.back(); }
});

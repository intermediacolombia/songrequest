// JavaScript Document
$(document).ready(function() {
  const tabla = $('#tablaAdmin').DataTable({
    ajax: 'actions.php?action=listar',
    responsive: true,
    columns: [
      { data: 'id' },
      { data: 'nombre' },
      { data: 'cancion' },
      { data: 'artista' },
      { data: 'genero' },
      { data: 'estado', render: estado => {
          if (estado === 'Pendiente') return '<span class="badge bg-warning text-dark">Pendiente</span>';
          if (estado === 'Sonada') return '<span class="badge bg-success">Sonada</span>';
          if (estado === 'Rechazada') return '<span class="badge bg-danger">Rechazada</span>';
          return '';
      }},
      { data: 'comentario', render: c => c ? `<i class="fas fa-comment text-danger" title="${c}"></i>` : '' },
      { data: 'fecha' },
      { data: 'hora' },
      { data: null, orderable: false, className: 'text-center',
        render: data => `
          <button class="btn btn-success btn-sm btn-action sonada" data-id="${data.id}" title="Marcar Sonada"><i class="fas fa-check"></i></button>
          <button class="btn btn-warning btn-sm btn-action pendiente" data-id="${data.id}" title="Marcar Pendiente"><i class="fa fa-clock"></i></button>
          <button class="btn btn-danger btn-sm btn-action rechazar" data-id="${data.id}" title="Rechazar"><i class="fas fa-times"></i></button>
          <button class="btn btn-info btn-sm btn-action comentar" data-id="${data.id}" title="Agregar Comentario"><i class="fas fa-comment-dots"></i></button>
          <button class="btn btn-secondary btn-sm btn-action borrar" data-id="${data.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
        `
      }
    ],
    order: [[0, 'desc']],
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    }
  });

  // =======================
  // EVENTOS DE ACCIONES
  // =======================
  $('#tablaAdmin').on('click', '.sonada', function() {
    cambiarEstado($(this).data('id'), 'Sonada');
  });

  $('#tablaAdmin').on('click', '.pendiente', function() {
    cambiarEstado($(this).data('id'), 'Pendiente');
  });

  $('#tablaAdmin').on('click', '.rechazar', function() {
    cambiarEstado($(this).data('id'), 'Rechazada');
  });

  $('#tablaAdmin').on('click', '.borrar', function() {
    eliminar($(this).data('id'));
  });

  $('#tablaAdmin').on('click', '.comentar', function() {
    $('#comentarioId').val($(this).data('id'));
    $('#comentarioTexto').val('');
    const modal = new bootstrap.Modal(document.getElementById('comentarioModal'));
    modal.show();
  });

  // Guardar comentario
  $('#guardarComentario').click(function() {
    const id = $('#comentarioId').val();
    const texto = $('#comentarioTexto').val().trim();
    if (!texto) return Swal.fire('Atención', 'El comentario no puede estar vacío', 'warning');
    $.post('actions.php?action=comentar', { id, comentario: texto }, function(res) {
      if (res.status === 'success') {
        Swal.fire('Guardado', res.message, 'success');
        $('#comentarioModal').modal('hide');
        tabla.ajax.reload(null, false);
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    }, 'json');
  });

  // =======================
  // FUNCIONES AJAX
  // =======================
  function cambiarEstado(id, estado) {
    $.post('actions.php?action=estado', { id, estado }, function(res) {
      if (res.status === 'success') {
        Swal.fire('Actualizado', res.message, 'success');
        tabla.ajax.reload(null, false);
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    }, 'json');
  }

  function eliminar(id) {
    Swal.fire({
      title: '¿Eliminar solicitud?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then(result => {
      if (result.isConfirmed) {
        $.post('actions.php?action=eliminar', { id }, function(res) {
          if (res.status === 'success') {
            Swal.fire('Eliminada', res.message, 'success');
            tabla.ajax.reload(null, false);
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json');
      }
    });
  }
});

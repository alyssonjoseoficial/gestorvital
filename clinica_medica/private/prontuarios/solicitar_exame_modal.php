<?php
$consulta_id = $_GET['consulta_id'] ?? 0;
?>

<form id="formSolicitarExame">
    <input type="hidden" name="consulta_id" value="<?= htmlspecialchars($consulta_id) ?>">
    <div class="mb-3">
        <label for="descricaoExame" class="form-label">Descrição dos Exames</label>
        <textarea class="form-control" id="descricaoExame" name="descricao" rows="5" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Salvar Solicitação</button>
</form>

<script>
document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'formSolicitarExame') {
        e.preventDefault();
        
        var form = e.target;
        var formData = new FormData(form);
        
        fetch('../prontuarios/salvar_solicitacao.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Recarrega o conteúdo da aba "solicitar-exame" para mostrar o histórico atualizado
                const currentConsultaId = formData.get('consulta_id');
                const url = `../prontuarios/visualizar_solicitacao.php?consulta_id=${currentConsultaId}`;
                const tabPane = document.getElementById('solicitar-exame');
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        tabPane.innerHTML = html;
                        const scripts = tabPane.querySelectorAll('script');
                        scripts.forEach(script => {
                            const newScript = document.createElement('script');
                            newScript.textContent = script.textContent;
                            document.body.appendChild(newScript);
                            newScript.onload = () => newScript.remove();
                        });
                    });
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao tentar salvar a solicitação.');
        });
    }
});
</script>
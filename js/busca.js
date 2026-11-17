'use strict';

// Seleciona os elementos da busca pelo id
const formBusca = document.querySelector("#form-busca");
const campoBusca = document.querySelector("#campo-busca");
const divResultados = document.querySelector("#resultados");

let temporizadorBusca;
// Identifica a consulta mais recente para descartar respostas antigas
let buscaAtual = 0;

// Enquanto o usuário digita, atualiza os resultados após uma pausa
campoBusca.addEventListener("input", function () {
    clearTimeout(temporizadorBusca);

    // Campo vazio (ou apenas com espaços): limpa e esconde os resultados
    if (campoBusca.value.trim() === "") {
        buscaAtual++;
        esconderResultados();
        return;
    }

    // Reinicia a espera a cada tecla: a consulta só começa após 300 ms
    // sem digitação. O número também invalida uma resposta pendente.
    const numeroBusca = ++buscaAtual;
    temporizadorBusca = setTimeout(() => realizarBusca(numeroBusca), 300);
});

function esconderResultados() {
    divResultados.setAttribute("hidden", "");
    divResultados.innerHTML = "";
    divResultados.setAttribute("aria-busy", "false");
}

async function realizarBusca(numeroBusca) {
    divResultados.removeAttribute("hidden");
    // Informa às tecnologias assistivas que a região está sendo atualizada
    divResultados.setAttribute("aria-busy", "true");

    try {
        // Envia o campo do formulário para o PHP
        const resposta = await fetch("resultados.php", {
            method: "POST",
            body: new FormData(formBusca)
        });

        if (!resposta.ok) {
            throw new Error("A busca retornou uma resposta inválida.");
        }

        // resultados.php devolve HTML com os valores já escapados
        const dados = await resposta.text();

        // Uma consulta mais nova começou enquanto esta aguardava a resposta
        if (numeroBusca !== buscaAtual) {
            return;
        }

        divResultados.innerHTML = dados;
    } catch (error) {
        if (numeroBusca !== buscaAtual) {
            return;
        }

        console.error("Falha ao buscar notícias.", error);

        const mensagemErro = document.createElement("p");
        mensagemErro.className = "alert alert-warning mb-0";
        mensagemErro.textContent = "Não foi possível realizar a busca agora.";
        divResultados.replaceChildren(mensagemErro);
    } finally {
        // Só encerra o estado ocupado da consulta mais recente
        if (numeroBusca === buscaAtual) {
            divResultados.setAttribute("aria-busy", "false");
        }
    }
}

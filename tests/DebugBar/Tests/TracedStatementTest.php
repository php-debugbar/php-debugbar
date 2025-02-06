<?php

namespace DebugBar\Tests;

use DebugBar\DataCollector\PDO\TracedStatement;

/**
 * Class TracedStatementTest
 * @package DebugBar\Tests
 */
class TracedStatementTest extends DebugBarTestCase
{
    /**
     * Check if query parameters are being replaced in the correct way
     * @bugFix Before fix it : select *
     *                          from geral.exame_part ep
     *                           where ep.id_exame = <1> and
     *                             ep.id_exame_situacao = <2>'
     *                            ep.id_exame_situacao = <1>_situacao
     * @return void
     */
    public function testReplacementParamsQuery()
    {
        $sql = 'select *
                from geral.exame_part ep
                where ep.id_exame = :id_exame and 
                      ep.id_exame_situacao = :id_exame_situacao';
        $params = array(
            ':id_exame'          => 1,
            ':id_exame_situacao' => 2
        );
        $traced = new TracedStatement($sql, $params);
        $expected = 'select *
                from geral.exame_part ep
                where ep.id_exame = <1> and 
                      ep.id_exame_situacao = <2>';
        $result = $traced->getSqlWithParams();
        $this->assertEquals($expected, $result);
    }

    public function testReplacementParamsContainingBackReferenceSyntaxGeneratesCorrectString()
    {
        $hashedPassword = '$2y$10$S3Y/kSsx8Z5BPtdd9.k3LOkbQ0egtsUHBT9EGQ.spxsmaEWbrxBW2';
        $sql = "UPDATE user SET password = :password";

        $params = array(
            ':password' => $hashedPassword,
        );

        $traced = new TracedStatement($sql, $params);

        $result = $traced->getSqlWithParams();

        $expected = "UPDATE user SET password = <$hashedPassword>";

        $this->assertEquals($expected, $result);
    }

    public function testReplacementParamsContainingPotentialAdditionalQuestionMarkPlaceholderGeneratesCorrectString()
    {
        $hasQuestionMark = "Asking a question?";
        $string = "Asking for a friend";

        $sql = "INSERT INTO questions SET question = ?, detail = ?";

        $params = array($hasQuestionMark, $string);

        $traced = new TracedStatement($sql, $params);

        $result = $traced->getSqlWithParams();

        $expected = "INSERT INTO questions SET question = <$hasQuestionMark>, detail = <$string>";

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams("'");

        $expected = "INSERT INTO questions SET question = '$hasQuestionMark', detail = '$string'";

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams('"');

        $expected = "INSERT INTO questions SET question = \"$hasQuestionMark\", detail = \"$string\"";

        $this->assertEquals($expected, $result);
    }

    public function testReplacementParamsContainingPotentialAdditionalNamedPlaceholderGeneratesCorrectString()
    {
        $hasQuestionMark = "Asking a question with a :string inside";
        $string = "Asking for a friend";

        $sql = "INSERT INTO questions SET question = :question, detail = :string";

        $params = array(
            ':question' => $hasQuestionMark,
            ':string'   => $string,
        );

        $traced = new TracedStatement($sql, $params);

        $result = $traced->getSqlWithParams();

        $expected = "INSERT INTO questions SET question = <$hasQuestionMark>, detail = <$string>";

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams("'");

        $expected = "INSERT INTO questions SET question = '$hasQuestionMark', detail = '$string'";

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams('"');

        $expected = "INSERT INTO questions SET question = \"$hasQuestionMark\", detail = \"$string\"";

        $this->assertEquals($expected, $result);
    }

    /**
     * Check if query parameters are being replaced in the correct way
     * @bugFix Before fix it : select *
     *                          from geral.person p
     *                           left join geral.contract c
     *                             on c.id_person = p.id_person
     *                           where c.status = <1> and
     *                           p.status <> :status;
     * @return void
     */
    public function testRepeadParamsQuery()
    {
        $sql = 'select *
                from geral.person p
                left join geral.contract c
                  on c.id_person = p.id_person
                where c.status = :status and 
                      p.status <> :status';
        $params = array(
            ':status' => 1
        );
        $traced = new TracedStatement($sql, $params);
        $expected = 'select *
                from geral.person p
                left join geral.contract c
                  on c.id_person = p.id_person
                where c.status = <1> and 
                      p.status <> <1>';
        $result = $traced->getSqlWithParams();
        $this->assertEquals($expected, $result);
    }

    /**
     * Big query generate regex error
     */
    public function testBigQuery()
    {
        $params = [
            ':id_segmento' => 1,
            ':id_part'     => 1,
            ':inativo'     => 1,
            ':ativo'       => 1
        ];
        $query = 'select sum(qtd) as total from (
                    select count(*) as qtd from (
                        select distinct vw.id_modulo, vw.id_envio, vw.id_form,
                            vw.versao, vw.id_sistema, vw.id_grupo
                        from vw_forms_disponiveis vw
                        where vw.id_part = :id_part
                            and vw.id_servico = :id_segmento
                            and vw.concluido = :inativo
                    ) as forms

                    union
                    select distinct count(pesq.*) as qtd from
                        pesquisa.avaliacao_modulo_disponivel pesq
                    join envio e
                        on e.id_envio = pesq.id_envio
                        and e.id_servico = pesq.id_servico
                    join rodada r
                        on e.id_envio = r.id_envio
                    join part_form pf
                        on r.ano = pf.ano
                        and r.id_rodada = pf.id_rodada
                        and pf.id_modulo = r.id_modulo
                    where pf.id_part = :id_part
                        and (
                            r.data_fim > date(now())
                            or pf.data_excecao_fim > date(now())
                        )
                        and (
                            r.data_ini <= date(now())
                            or pf.data_excecao_ini <= date(now())
                        )
                        and e.id_servico = :id_segmento
                        and pesq.id_modulo = :inativo

                    union
                    select distinct count(p.id_pesquisa) as qtd
                    from pesquisas.pesquisa p
                    join pesquisas.publicacao as pub
                        on pub.id_pesquisa = p.id_pesquisa
                    left join pesquisas.resposta r
                        on r.id_pesquisa = p.id_pesquisa
                        and r.id_publicacao = pub.id_publicacao
                        and r.id_part = :id_part
                    where pub.id_servico = :id_segmento
                        and pub.excluido = :inativo
                        and p.excluido = :inativo
                        and r.id_resposta is null 
                        and pub.data_ini <= date(now())
                        and pub.data_fim >= date (now())
                        and (
                            p.resp_primeira_publicacao = :inativo
                            or (
                                    pub.id_publicacao in (
                                            select r2.id_publicacao
                                            from pesquisas.resposta r2
                                            where r2.id_part = :id_part
                                                and r2.id_pesquisa = p.id_pesquisa
                                    )
                            )
                            or (
                                    not exists (
                                            select r2.id_pesquisa
                                            from pesquisas.resposta r2
                                            where r2.id_part = :id_part
                                                and r2.id_pesquisa = p.id_pesquisa
                                    )
                            )
                        )
                        and (
                            pub.todos_parts = :ativo
                            or :id_part in (
                                select id_part
                                from pesquisas.publicacao_part pp
                                where pp.id_pesquisa = pub.id_pesquisa
                                    and pp.id_publicacao = pub.id_publicacao
                                    and pp.id_part = :id_part
                            )
                        )

                    union
                    select count(es.*) as qtd from especial.solicitacao es
                    join especial.dados_solicitacao d
                        on es.id_servico = d.id_servico
                        and es.ano = d.ano
                    join especial.liberacao el
                        on el.ano = d.ano
                        and el.id_servico = d.id_servico
                        and el.id_part = es.id_part
                    left join especial.prorrogados p
                        on p.ano = el.ano
                        and p.id_servico = el.id_servico
                        and p.id_part = el.id_part
                    where es.id_usu is null -- possui resp
                        and el.id_servico = :id_segmento
                        and el.id_part = :id_part
                        and (
                            el.id_part in (8005, 8012)
                            or
                            d.liberada = :ativo
                        )
                        and ((d.data_ini <= date(now()) and d.data_fim >= date(now()))
                            or (
                                d.data_ini_excecao <= date(now())
                                and d.data_fim_excecao >= date(now())
                                and p.id_part not in (
                                    select s.id_part from especial.solicitacao s
                                    where s.data < d.data_ini_excecao
                                        and s.id_part = el.id_part and s.ano = d.ano
                                        and s.id_servico = d.id_servico
                                )
                            )
                            or (p.data_fim >= date(now()))
                        )
                    ) as resultados_em_aberto';
        $expected = 'select sum(qtd) as total from (
                    select count(*) as qtd from (
                        select distinct vw.id_modulo, vw.id_envio, vw.id_form,
                            vw.versao, vw.id_sistema, vw.id_grupo
                        from vw_forms_disponiveis vw
                        where vw.id_part = <1>
                            and vw.id_servico = <1>
                            and vw.concluido = <1>
                    ) as forms

                    union
                    select distinct count(pesq.*) as qtd from
                        pesquisa.avaliacao_modulo_disponivel pesq
                    join envio e
                        on e.id_envio = pesq.id_envio
                        and e.id_servico = pesq.id_servico
                    join rodada r
                        on e.id_envio = r.id_envio
                    join part_form pf
                        on r.ano = pf.ano
                        and r.id_rodada = pf.id_rodada
                        and pf.id_modulo = r.id_modulo
                    where pf.id_part = <1>
                        and (
                            r.data_fim > date(now())
                            or pf.data_excecao_fim > date(now())
                        )
                        and (
                            r.data_ini <= date(now())
                            or pf.data_excecao_ini <= date(now())
                        )
                        and e.id_servico = <1>
                        and pesq.id_modulo = <1>

                    union
                    select distinct count(p.id_pesquisa) as qtd
                    from pesquisas.pesquisa p
                    join pesquisas.publicacao as pub
                        on pub.id_pesquisa = p.id_pesquisa
                    left join pesquisas.resposta r
                        on r.id_pesquisa = p.id_pesquisa
                        and r.id_publicacao = pub.id_publicacao
                        and r.id_part = <1>
                    where pub.id_servico = <1>
                        and pub.excluido = <1>
                        and p.excluido = <1>
                        and r.id_resposta is null 
                        and pub.data_ini <= date(now())
                        and pub.data_fim >= date (now())
                        and (
                            p.resp_primeira_publicacao = <1>
                            or (
                                    pub.id_publicacao in (
                                            select r2.id_publicacao
                                            from pesquisas.resposta r2
                                            where r2.id_part = <1>
                                                and r2.id_pesquisa = p.id_pesquisa
                                    )
                            )
                            or (
                                    not exists (
                                            select r2.id_pesquisa
                                            from pesquisas.resposta r2
                                            where r2.id_part = <1>
                                                and r2.id_pesquisa = p.id_pesquisa
                                    )
                            )
                        )
                        and (
                            pub.todos_parts = <1>
                            or <1> in (
                                select id_part
                                from pesquisas.publicacao_part pp
                                where pp.id_pesquisa = pub.id_pesquisa
                                    and pp.id_publicacao = pub.id_publicacao
                                    and pp.id_part = <1>
                            )
                        )

                    union
                    select count(es.*) as qtd from especial.solicitacao es
                    join especial.dados_solicitacao d
                        on es.id_servico = d.id_servico
                        and es.ano = d.ano
                    join especial.liberacao el
                        on el.ano = d.ano
                        and el.id_servico = d.id_servico
                        and el.id_part = es.id_part
                    left join especial.prorrogados p
                        on p.ano = el.ano
                        and p.id_servico = el.id_servico
                        and p.id_part = el.id_part
                    where es.id_usu is null -- possui resp
                        and el.id_servico = <1>
                        and el.id_part = <1>
                        and (
                            el.id_part in (8005, 8012)
                            or
                            d.liberada = <1>
                        )
                        and ((d.data_ini <= date(now()) and d.data_fim >= date(now()))
                            or (
                                d.data_ini_excecao <= date(now())
                                and d.data_fim_excecao >= date(now())
                                and p.id_part not in (
                                    select s.id_part from especial.solicitacao s
                                    where s.data < d.data_ini_excecao
                                        and s.id_part = el.id_part and s.ano = d.ano
                                        and s.id_servico = d.id_servico
                                )
                            )
                            or (p.data_fim >= date(now()))
                        )
                    ) as resultados_em_aberto';
        $traced = new TracedStatement($query, $params);
        $result = $traced->getSqlWithParams();
        $this->assertEquals($expected, $result);
    }

    /**
     * Check that query parameters are being replaced only once
     * @bugFix Before fix it: select * from
     *                          `my_table` where `my_field` between
     *                           <2018-01-01> and <2018-01-01>
     * @return void
     */
    public function testParametersAreNotRepeated()
    {
        $query = 'select * from `my_table` where `my_field` between ? and ?';
        $bindings = [
            '2018-01-01',
            '2020-09-01',
        ];

        $this->assertEquals(
            'select * from `my_table` where `my_field` between <2018-01-01> and <2020-09-01>',
            (new TracedStatement($query, $bindings))->getSqlWithParams()
        );
    }
}

<?php

declare(strict_types=1);

/**
 * Catálogo MVP — materiais de construção (dados coerentes para validação ERP + vitrine).
 *
 * @return array{
 *     brands: list<array{name: string, slug: string}>,
 *     departments: list<array{
 *         nome: string,
 *         slug: string,
 *         sort_order: int,
 *         meta_description: string,
 *         children: list<array{
 *             nome: string,
 *             slug: string,
 *             sort_order: int,
 *             products: list<array{
 *                 nome: string,
 *                 sku: string,
 *                 brand_slug: string,
 *                 unit_code: string,
 *                 preco: float,
 *                 compare_at_price: float|null,
 *                 estoque: int,
 *                 short_description: string,
 *                 long_description: string
 *             }>
 *         }>
 *     }>
 * }
 */
return [
    'brands' => [
        ['name' => 'Votorantim Cimentos', 'slug' => 'votorantim'],
        ['name' => 'Quartzolit', 'slug' => 'quartzolit'],
        ['name' => 'Tigre', 'slug' => 'tigre'],
        ['name' => 'Deca', 'slug' => 'deca'],
        ['name' => 'Lorenzetti', 'slug' => 'lorenzetti'],
        ['name' => 'Intelbras', 'slug' => 'intelbras'],
        ['name' => 'Tramontina', 'slug' => 'tramontina'],
        ['name' => 'Bosch', 'slug' => 'bosch'],
        ['name' => 'Portobello', 'slug' => 'portobello'],
        ['name' => 'Suvinil', 'slug' => 'suvinil'],
        ['name' => 'Coral', 'slug' => 'coral'],
        ['name' => 'Elgin', 'slug' => 'elgin'],
    ],
    'departments' => [
        [
            'nome' => 'Material Básico',
            'slug' => 'material-basico',
            'sort_order' => 1,
            'meta_description' => 'Cimentos, argamassas, agregados e insumos para fundação e alvenaria.',
            'children' => [
                [
                    'nome' => 'Cimentos',
                    'slug' => 'material-basico-cimentos',
                    'sort_order' => 1,
                    'products' => [
                        ['nome' => 'Cimento Portland CP II 50 kg', 'sku' => 'MB-CIM-001', 'brand_slug' => 'votorantim', 'unit_code' => 'SC', 'preco' => 32.90, 'compare_at_price' => 36.90, 'estoque' => 120, 'short_description' => 'Cimento CP II saco 50 kg para uso geral em obras.', 'long_description' => 'Cimento Portland CP II 50 kg, indicado para concreto, argamassas e estruturas. Alto desempenho em obras residenciais e comerciais.'],
                        ['nome' => 'Cimento Portland CP III 50 kg', 'sku' => 'MB-CIM-002', 'brand_slug' => 'votorantim', 'unit_code' => 'SC', 'preco' => 31.50, 'compare_at_price' => null, 'estoque' => 95, 'short_description' => 'Cimento CP III com resistência intermediária.', 'long_description' => 'Cimento CP III 50 kg para estruturas que exigem boa trabalhabilidade e cura adequada.'],
                        ['nome' => 'Cimento Branco Estrutural 40 kg', 'sku' => 'MB-CIM-003', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 48.90, 'compare_at_price' => 52.90, 'estoque' => 40, 'short_description' => 'Cimento branco para acabamentos e fachadas.', 'long_description' => 'Cimento branco estrutural 40 kg ideal para revestimentos, contrapisos e acabamentos especiais.'],
                        ['nome' => 'Cimento de Alto Forno CP IV 50 kg', 'sku' => 'MB-CIM-004', 'brand_slug' => 'votorantim', 'unit_code' => 'SC', 'preco' => 29.90, 'compare_at_price' => null, 'estoque' => 80, 'short_description' => 'CP IV para concretos com baixo calor de hidratação.', 'long_description' => 'Cimento CP IV 50 kg recomendado para volumes massivos e estruturas expostas.'],
                    ],
                ],
                [
                    'nome' => 'Argamassas',
                    'slug' => 'material-basico-argamassas',
                    'sort_order' => 2,
                    'products' => [
                        ['nome' => 'Argamassa Colante AC-I 20 kg', 'sku' => 'MB-ARG-001', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 24.90, 'compare_at_price' => null, 'estoque' => 150, 'short_description' => 'Argamassa AC-I para cerâmica em áreas internas.', 'long_description' => 'Argamassa colante AC-I 20 kg para assentamento de pisos e revestimentos em áreas secas.'],
                        ['nome' => 'Argamassa Colante AC-III 20 kg', 'sku' => 'MB-ARG-002', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 32.50, 'compare_at_price' => 35.90, 'estoque' => 110, 'short_description' => 'Alta aderência para porcelanato grande formato.', 'long_description' => 'Argamassa AC-III 20 kg para porcelanatos e áreas com maior exigência de aderência.'],
                        ['nome' => 'Argamassa de Assentamento e Rejuntamento 20 kg', 'sku' => 'MB-ARG-003', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 19.90, 'compare_at_price' => null, 'estoque' => 90, 'short_description' => 'Argamassa tradicional para alvenaria e rejunte.', 'long_description' => 'Argamassa multiuso 20 kg para assentamento de blocos e rejuntes em alvenaria.'],
                        ['nome' => 'Chapisco Quartzolit 20 kg', 'sku' => 'MB-ARG-004', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 18.50, 'compare_at_price' => null, 'estoque' => 70, 'short_description' => 'Chapisco pronto para regularização de paredes.', 'long_description' => 'Chapisco 20 kg de fácil aplicação para preparo de superfícies antes do reboco.'],
                    ],
                ],
                [
                    'nome' => 'Areia e Brita',
                    'slug' => 'material-basico-agregados',
                    'sort_order' => 3,
                    'products' => [
                        ['nome' => 'Areia Média Lavada m³', 'sku' => 'MB-AGR-001', 'brand_slug' => 'votorantim', 'unit_code' => 'M3', 'preco' => 185.00, 'compare_at_price' => null, 'estoque' => 25, 'short_description' => 'Areia média para concreto e argamassa.', 'long_description' => 'Areia média lavada por m³, agregado para concretagem e assentamento.'],
                        ['nome' => 'Brita 1 Pedrisco m³', 'sku' => 'MB-AGR-002', 'brand_slug' => 'votorantim', 'unit_code' => 'M3', 'preco' => 210.00, 'compare_at_price' => 225.00, 'estoque' => 20, 'short_description' => 'Brita 1 para concreto estrutural.', 'long_description' => 'Brita 1 (pedrisco) m³ para concretos e bases estruturais.'],
                        ['nome' => 'Brita 0 Pó de Pedra m³', 'sku' => 'MB-AGR-003', 'brand_slug' => 'votorantim', 'unit_code' => 'M3', 'preco' => 198.00, 'compare_at_price' => null, 'estoque' => 18, 'short_description' => 'Brita 0 para contrapiso e acabamento.', 'long_description' => 'Brita 0 m³ indicada para contrapisos, lastros e concretos finos.'],
                        ['nome' => 'Areia Fina para Rejunte 25 kg', 'sku' => 'MB-AGR-004', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 12.90, 'compare_at_price' => null, 'estoque' => 200, 'short_description' => 'Areia fina ensacada para rejuntamento.', 'long_description' => 'Areia fina 25 kg para rejuntamento e argamassas especiais.'],
                    ],
                ],
                [
                    'nome' => 'Ferragens Básicas',
                    'slug' => 'material-basico-ferragens',
                    'sort_order' => 4,
                    'products' => [
                        ['nome' => 'Prego 18x27 Polido 1 kg', 'sku' => 'MB-FER-001', 'brand_slug' => 'tramontina', 'unit_code' => 'KG', 'preco' => 14.90, 'compare_at_price' => null, 'estoque' => 80, 'short_description' => 'Prego polido para carpintaria e estruturas leves.', 'long_description' => 'Prego 18x27 polido pacote 1 kg para montagem de estruturas de madeira.'],
                        ['nome' => 'Arame Recozido Nº 18 Rolo 1 kg', 'sku' => 'MB-FER-002', 'brand_slug' => 'tramontina', 'unit_code' => 'RL', 'preco' => 11.50, 'compare_at_price' => null, 'estoque' => 60, 'short_description' => 'Arame para amarração de ferragem.', 'long_description' => 'Arame recozido Nº 18 rolo 1 kg para amarração em obras de concreto.'],
                        ['nome' => 'Tela Soldada Q-92 2,0 x 3,0 m', 'sku' => 'MB-FER-003', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 89.90, 'compare_at_price' => 99.90, 'estoque' => 35, 'short_description' => 'Tela soldada para laje e piso.', 'long_description' => 'Tela soldada Q-92 2,0 x 3,0 m para armadura de pisos e lajes maciças.'],
                        ['nome' => 'Vergalhão CA-50 10 mm 12 m', 'sku' => 'MB-FER-004', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 58.00, 'compare_at_price' => null, 'estoque' => 45, 'short_description' => 'Vergalhão 10 mm para estruturas de concreto.', 'long_description' => 'Vergalhão CA-50 10 mm barra 12 m para armadura estrutural.'],
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Hidráulica',
            'slug' => 'hidraulica',
            'sort_order' => 2,
            'meta_description' => 'Torneiras, tubos, conexões, registros e louças para instalações hidráulicas.',
            'children' => [
                [
                    'nome' => 'Torneiras',
                    'slug' => 'hidraulica-torneiras',
                    'sort_order' => 1,
                    'products' => [
                        ['nome' => 'Torneira para Cozinha de Mesa Bica Alta', 'sku' => 'HD-TOR-001', 'brand_slug' => 'deca', 'unit_code' => 'UN', 'preco' => 189.90, 'compare_at_price' => 219.90, 'estoque' => 25, 'short_description' => 'Torneira cromada bica alta para cozinha.', 'long_description' => 'Torneira de mesa bica alta cromada, design contemporâneo para cozinhas residenciais.'],
                        ['nome' => 'Torneira para Lavatório Link', 'sku' => 'HD-TOR-002', 'brand_slug' => 'deca', 'unit_code' => 'UN', 'preco' => 129.90, 'compare_at_price' => null, 'estoque' => 30, 'short_description' => 'Torneira link para banheiro.', 'long_description' => 'Torneira para lavatório linha Link com acabamento cromado e cartucho cerâmico.'],
                        ['nome' => 'Torneira Gourmet com Spray', 'sku' => 'HD-TOR-003', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 349.00, 'compare_at_price' => 389.00, 'estoque' => 12, 'short_description' => 'Torneira gourmet com mangueira spray.', 'long_description' => 'Torneira gourmet Tigre com bica flexível e função spray para cozinhas profissionais.'],
                        ['nome' => 'Torneira para Tanque Parede', 'sku' => 'HD-TOR-004', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 79.90, 'compare_at_price' => null, 'estoque' => 40, 'short_description' => 'Torneira de parede para área de serviço.', 'long_description' => 'Torneira para tanque de parede, robusta para áreas de serviço e lavanderia.'],
                    ],
                ],
                [
                    'nome' => 'Tubos e Conexões',
                    'slug' => 'hidraulica-tubos',
                    'sort_order' => 2,
                    'products' => [
                        ['nome' => 'Tubo PVC Soldável 25 mm 6 m', 'sku' => 'HD-TUB-001', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 18.90, 'compare_at_price' => null, 'estoque' => 200, 'short_description' => 'Tubo PVC 25 mm para água fria.', 'long_description' => 'Tubo PVC soldável 25 mm barra 6 m para instalações de água fria predial.'],
                        ['nome' => 'Tubo PVC Esgoto 100 mm 6 m', 'sku' => 'HD-TUB-002', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 54.90, 'compare_at_price' => 59.90, 'estoque' => 80, 'short_description' => 'Tubo de esgoto PVC 100 mm.', 'long_description' => 'Tubo PVC para esgoto 100 mm 6 m, anel de borracha para vedação.'],
                        ['nome' => 'Joelho 90° PVC Soldável 25 mm', 'sku' => 'HD-TUB-003', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 2.40, 'compare_at_price' => null, 'estoque' => 500, 'short_description' => 'Conexão joelho 90° 25 mm.', 'long_description' => 'Joelho 90° PVC soldável 25 mm para mudança de direção em instalações.'],
                        ['nome' => 'Registro de Gaveta 3/4" Bronze', 'sku' => 'HD-TUB-004', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 42.00, 'compare_at_price' => null, 'estoque' => 55, 'short_description' => 'Registro de gaveta 3/4 em bronze.', 'long_description' => 'Registro de gaveta 3/4 bronze para controle de fluxo em instalações prediais.'],
                    ],
                ],
                [
                    'nome' => 'Registros e Válvulas',
                    'slug' => 'hidraulica-registros',
                    'sort_order' => 3,
                    'products' => [
                        ['nome' => 'Registro Esfera 1/2" PVC', 'sku' => 'HD-REG-001', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 12.90, 'compare_at_price' => null, 'estoque' => 90, 'short_description' => 'Registro esfera 1/2 para água fria.', 'long_description' => 'Registro de esfera 1/2 PVC com alavanca para fechamento rápido.'],
                        ['nome' => 'Válvula de Descarga Social', 'sku' => 'HD-REG-002', 'brand_slug' => 'deca', 'unit_code' => 'UN', 'preco' => 98.00, 'compare_at_price' => 110.00, 'estoque' => 22, 'short_description' => 'Acionamento para caixa acoplada.', 'long_description' => 'Válvula de descarga social Deca para caixas acopladas com economia de água.'],
                        ['nome' => 'Válvula de Retenção 50 mm', 'sku' => 'HD-REG-003', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 34.50, 'compare_at_price' => null, 'estoque' => 35, 'short_description' => 'Válvula de retenção para esgoto.', 'long_description' => 'Válvula de retenção 50 mm evita retorno de efluentes em redes de esgoto.'],
                        ['nome' => 'Registro Pressão 3/4"', 'sku' => 'HD-REG-004', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 28.90, 'compare_at_price' => null, 'estoque' => 48, 'short_description' => 'Registro de pressão 3/4.', 'long_description' => 'Registro de pressão 3/4 para instalações com maior exigência de vedação.'],
                    ],
                ],
                [
                    'nome' => 'Caixas e Louças',
                    'slug' => 'hidraulica-loucas',
                    'sort_order' => 4,
                    'products' => [
                        ['nome' => 'Caixa Acoplada com Acionamento', 'sku' => 'HD-LOU-001', 'brand_slug' => 'deca', 'unit_code' => 'UN', 'preco' => 389.00, 'compare_at_price' => 429.00, 'estoque' => 15, 'short_description' => 'Caixa acoplada para vaso sanitário.', 'long_description' => 'Caixa acoplada Deca com kit de instalação e acionamento duplo.'],
                        ['nome' => 'Vaso Sanitário com Caixa Acoplada', 'sku' => 'HD-LOU-002', 'brand_slug' => 'deca', 'unit_code' => 'UN', 'preco' => 549.00, 'compare_at_price' => null, 'estoque' => 10, 'short_description' => 'Conjunto vaso + caixa acoplada.', 'long_description' => 'Vaso sanitário com caixa acoplada, saída horizontal, linha convencional.'],
                        ['nome' => 'Cuba de Embutir Retangular', 'sku' => 'HD-LOU-003', 'brand_slug' => 'deca', 'unit_code' => 'UN', 'preco' => 259.00, 'compare_at_price' => 289.00, 'estoque' => 14, 'short_description' => 'Cuba retangular para banheiro.', 'long_description' => 'Cuba de embutir retangular em louça sanitária branca.'],
                        ['nome' => 'Ralo Seco 100 mm', 'sku' => 'HD-LOU-004', 'brand_slug' => 'tigre', 'unit_code' => 'UN', 'preco' => 24.90, 'compare_at_price' => null, 'estoque' => 65, 'short_description' => 'Ralo seco com grelha inox.', 'long_description' => 'Ralo seco 100 mm com grelha inox para áreas molhadas e box.'],
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Elétrica',
            'slug' => 'eletrica',
            'sort_order' => 3,
            'meta_description' => 'Fios, cabos, disjuntores, tomadas e iluminação para instalações elétricas.',
            'children' => [
                [
                    'nome' => 'Fios e Cabos',
                    'slug' => 'eletrica-fios',
                    'sort_order' => 1,
                    'products' => [
                        ['nome' => 'Cabo Flexível 2,5 mm² Azul 100 m', 'sku' => 'EL-FIO-001', 'brand_slug' => 'intelbras', 'unit_code' => 'RL', 'preco' => 289.00, 'compare_at_price' => 310.00, 'estoque' => 20, 'short_description' => 'Rolo 100 m cabo flex 2,5 mm².', 'long_description' => 'Cabo flexível 750V 2,5 mm² azul rolo 100 m para circuitos de tomadas.'],
                        ['nome' => 'Cabo Flexível 4 mm² Preto 100 m', 'sku' => 'EL-FIO-002', 'brand_slug' => 'intelbras', 'unit_code' => 'RL', 'preco' => 459.00, 'compare_at_price' => null, 'estoque' => 15, 'short_description' => 'Cabo 4 mm² para circuitos de maior carga.', 'long_description' => 'Cabo flexível 4 mm² preto 100 m para chuveiros e equipamentos.'],
                        ['nome' => 'Cabo PP 3 x 2,5 mm² 100 m', 'sku' => 'EL-FIO-003', 'brand_slug' => 'intelbras', 'unit_code' => 'RL', 'preco' => 620.00, 'compare_at_price' => null, 'estoque' => 8, 'short_description' => 'Cabo PP para alimentação trifilar.', 'long_description' => 'Cabo PP 3 condutores 2,5 mm² 100 m para quadros e circuitos dedicados.'],
                        ['nome' => 'Fio Rígido 1,5 mm² Verde 100 m', 'sku' => 'EL-FIO-004', 'brand_slug' => 'intelbras', 'unit_code' => 'RL', 'preco' => 145.00, 'compare_at_price' => null, 'estoque' => 25, 'short_description' => 'Fio de aterramento 1,5 mm².', 'long_description' => 'Fio rígido 1,5 mm² verde 100 m para aterramento de circuitos.'],
                    ],
                ],
                [
                    'nome' => 'Disjuntores e Quadros',
                    'slug' => 'eletrica-disjuntores',
                    'sort_order' => 2,
                    'products' => [
                        ['nome' => 'Disjuntor Monopolar 20 A', 'sku' => 'EL-DIS-001', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 18.90, 'compare_at_price' => null, 'estoque' => 100, 'short_description' => 'Disjuntor DIN 20 A curva C.', 'long_description' => 'Disjuntor termomagnético monopolar 20 A curva C para quadros residenciais.'],
                        ['nome' => 'Disjuntor DR 40 A 30 mA', 'sku' => 'EL-DIS-002', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 189.00, 'compare_at_price' => 210.00, 'estoque' => 18, 'short_description' => 'DR 40 A alta sensibilidade.', 'long_description' => 'Disjuntor diferencial residual 40 A 30 mA para proteção contra choques.'],
                        ['nome' => 'Quadro de Distribuição 12 Disjuntores', 'sku' => 'EL-DIS-003', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 98.00, 'compare_at_price' => null, 'estoque' => 30, 'short_description' => 'Quadro embutir 12 disjuntores.', 'long_description' => 'Quadro de distribuição embutir para até 12 disjuntores DIN.'],
                        ['nome' => 'Barramento Trifásico 12 Módulos', 'sku' => 'EL-DIS-004', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 42.00, 'compare_at_price' => null, 'estoque' => 40, 'short_description' => 'Barramento para organização do quadro.', 'long_description' => 'Barramento trifásico 12 módulos para interligação de disjuntores.'],
                    ],
                ],
                [
                    'nome' => 'Tomadas e Interruptores',
                    'slug' => 'eletrica-tomadas',
                    'sort_order' => 3,
                    'products' => [
                        ['nome' => 'Tomada 2P+T 10A Padrão', 'sku' => 'EL-TOM-001', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 8.90, 'compare_at_price' => null, 'estoque' => 300, 'short_description' => 'Tomada 10A linha padrão branca.', 'long_description' => 'Tomada 2P+T 10A branca para instalações residenciais.'],
                        ['nome' => 'Interruptor Simples', 'sku' => 'EL-TOM-002', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 6.50, 'compare_at_price' => null, 'estoque' => 280, 'short_description' => 'Interruptor simples 10A.', 'long_description' => 'Interruptor simples branco 10A 250V.'],
                        ['nome' => 'Tomada USB + 2P+T', 'sku' => 'EL-TOM-003', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 54.90, 'compare_at_price' => 62.00, 'estoque' => 45, 'short_description' => 'Tomada com carregador USB integrado.', 'long_description' => 'Tomada combinada 2P+T com portas USB para carregamento de dispositivos.'],
                        ['nome' => 'Interruptor Paralelo', 'sku' => 'EL-TOM-004', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 12.90, 'compare_at_price' => null, 'estoque' => 120, 'short_description' => 'Interruptor paralelo para três pontos.', 'long_description' => 'Interruptor paralelo para acionamento de lâmpadas em três locais.'],
                    ],
                ],
                [
                    'nome' => 'Lâmpadas e Luminárias',
                    'slug' => 'eletrica-lampadas',
                    'sort_order' => 4,
                    'products' => [
                        ['nome' => 'Lâmpada LED Bulbo 9W E27', 'sku' => 'EL-LAM-001', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 12.90, 'compare_at_price' => 15.90, 'estoque' => 200, 'short_description' => 'LED 9W luz branca 6500K.', 'long_description' => 'Lâmpada LED bulbo 9W E27 luz branca fria, alta eficiência.'],
                        ['nome' => 'Lâmpada LED Tubular 18W', 'sku' => 'EL-LAM-002', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 24.00, 'compare_at_price' => null, 'estoque' => 90, 'short_description' => 'Lâmpada tubular para luminárias de teto.', 'long_description' => 'Lâmpada LED tubular 18W para substituição de fluorescentes.'],
                        ['nome' => 'Luminária de Embutir LED 18W', 'sku' => 'EL-LAM-003', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 49.90, 'compare_at_price' => 59.00, 'estoque' => 55, 'short_description' => 'Luminária quadrada de embutir.', 'long_description' => 'Luminária de embutir LED 18W quadrada para forros de gesso.'],
                        ['nome' => 'Refletor LED 50W Externo', 'sku' => 'EL-LAM-004', 'brand_slug' => 'intelbras', 'unit_code' => 'UN', 'preco' => 89.00, 'compare_at_price' => null, 'estoque' => 28, 'short_description' => 'Refletor 50W para áreas externas.', 'long_description' => 'Refletor LED 50W IP65 para fachadas, quintais e estacionamentos.'],
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Ferramentas',
            'slug' => 'ferramentas',
            'sort_order' => 4,
            'meta_description' => 'Ferramentas manuais, elétricas, medição e EPI para obra e manutenção.',
            'children' => [
                [
                    'nome' => 'Ferramentas Manuais',
                    'slug' => 'ferramentas-manuais',
                    'sort_order' => 1,
                    'products' => [
                        ['nome' => 'Martelo Unha 23 mm Cabo em Fibra', 'sku' => 'FT-MAN-001', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 34.90, 'compare_at_price' => null, 'estoque' => 50, 'short_description' => 'Martelo unha profissional.', 'long_description' => 'Martelo unha 23 mm com cabo em fibra de vidro antiderrapante.'],
                        ['nome' => 'Jogo Chave Phillips e Fenda 6 peças', 'sku' => 'FT-MAN-002', 'brand_slug' => 'tramontina', 'unit_code' => 'CX', 'preco' => 29.90, 'compare_at_price' => 34.90, 'estoque' => 40, 'short_description' => 'Kit chaves isoladas.', 'long_description' => 'Jogo com 6 chaves Phillips e fenda para uso geral.'],
                        ['nome' => 'Alicate Universal 8"', 'sku' => 'FT-MAN-003', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 42.00, 'compare_at_price' => null, 'estoque' => 35, 'short_description' => 'Alicate universal 8 polegadas.', 'long_description' => 'Alicate universal 8 pol com cabo emborrachado.'],
                        ['nome' => 'Trena 5 m com Trava', 'sku' => 'FT-MAN-004', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 24.90, 'compare_at_price' => null, 'estoque' => 60, 'short_description' => 'Trena 5 m para medições em obra.', 'long_description' => 'Trena 5 m com trava e fita revestida em nylon.'],
                    ],
                ],
                [
                    'nome' => 'Ferramentas Elétricas',
                    'slug' => 'ferramentas-eletricas',
                    'sort_order' => 2,
                    'products' => [
                        ['nome' => 'Furadeira de Impacto 650 W', 'sku' => 'FT-ELE-001', 'brand_slug' => 'bosch', 'unit_code' => 'UN', 'preco' => 289.00, 'compare_at_price' => 329.00, 'estoque' => 18, 'short_description' => 'Furadeira impacto 650 W.', 'long_description' => 'Furadeira de impacto 650 W com mandril 13 mm para concreto e metal.'],
                        ['nome' => 'Parafusadeira e Furadeira a Bateria 12V', 'sku' => 'FT-ELE-002', 'brand_slug' => 'bosch', 'unit_code' => 'UN', 'preco' => 449.00, 'compare_at_price' => null, 'estoque' => 12, 'short_description' => 'Kit 12V com bateria e carregador.', 'long_description' => 'Parafusadeira/furadeira 12V com 2 baterias e maleta.'],
                        ['nome' => 'Esmerilhadeira Angular 4 1/2" 720 W', 'sku' => 'FT-ELE-003', 'brand_slug' => 'bosch', 'unit_code' => 'UN', 'preco' => 319.00, 'compare_at_price' => 349.00, 'estoque' => 14, 'short_description' => 'Esmerilhadeira 720 W.', 'long_description' => 'Esmerilhadeira angular 4 1/2 pol 720 W para corte e desbaste.'],
                        ['nome' => 'Serra Mármore 1300 W', 'sku' => 'FT-ELE-004', 'brand_slug' => 'bosch', 'unit_code' => 'UN', 'preco' => 389.00, 'compare_at_price' => null, 'estoque' => 10, 'short_description' => 'Serra mármore profissional.', 'long_description' => 'Serra mármore 1300 W para cortes em porcelanato e granito.'],
                    ],
                ],
                [
                    'nome' => 'Medição',
                    'slug' => 'ferramentas-medicao',
                    'sort_order' => 3,
                    'products' => [
                        ['nome' => 'Nível de Bolha 60 cm', 'sku' => 'FT-MED-001', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 38.90, 'compare_at_price' => null, 'estoque' => 30, 'short_description' => 'Nível alumínio 60 cm.', 'long_description' => 'Nível de bolha alumínio 60 cm com três ampolas.'],
                        ['nome' => 'Esquadro Profissional 12"', 'sku' => 'FT-MED-002', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 32.00, 'compare_at_price' => null, 'estoque' => 25, 'short_description' => 'Esquadro para marcenaria e obra.', 'long_description' => 'Esquadro profissional 12 pol em aço para marcação precisa.'],
                        ['nome' => 'Trena a Laser 40 m', 'sku' => 'FT-MED-003', 'brand_slug' => 'bosch', 'unit_code' => 'UN', 'preco' => 259.00, 'compare_at_price' => 289.00, 'estoque' => 8, 'short_description' => 'Medidor laser 40 m.', 'long_description' => 'Trena laser 40 m com precisão milimétrica para ambientes internos.'],
                        ['nome' => 'Kit Disco Corte 4 1/2" 5 peças', 'sku' => 'FT-MED-004', 'brand_slug' => 'bosch', 'unit_code' => 'CX', 'preco' => 45.90, 'compare_at_price' => null, 'estoque' => 40, 'short_description' => 'Discos para esmerilhadeira.', 'long_description' => 'Kit 5 discos de corte 4 1/2 pol para metal e inox.'],
                    ],
                ],
                [
                    'nome' => 'EPI',
                    'slug' => 'ferramentas-epi',
                    'sort_order' => 4,
                    'products' => [
                        ['nome' => 'Capacete de Segurança Classe B', 'sku' => 'FT-EPI-001', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 22.90, 'compare_at_price' => null, 'estoque' => 80, 'short_description' => 'Capacete classe B branco.', 'long_description' => 'Capacete de segurança classe B com jugular ajustável.'],
                        ['nome' => 'Luva de Raspa Par', 'sku' => 'FT-EPI-002', 'brand_slug' => 'tramontina', 'unit_code' => 'PC', 'preco' => 14.50, 'compare_at_price' => null, 'estoque' => 100, 'short_description' => 'Luva raspa para obra pesada.', 'long_description' => 'Par de luvas de raspa reforçada para manuseio de materiais.'],
                        ['nome' => 'Óculos de Proteção Incolor', 'sku' => 'FT-EPI-003', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 9.90, 'compare_at_price' => null, 'estoque' => 150, 'short_description' => 'Óculos de proteção universal.', 'long_description' => 'Óculos de proteção incolor com hastes reguláveis.'],
                        ['nome' => 'Protetor Auricular Plug', 'sku' => 'FT-EPI-004', 'brand_slug' => 'tramontina', 'unit_code' => 'PC', 'preco' => 6.90, 'compare_at_price' => null, 'estoque' => 200, 'short_description' => 'Protetor auricular descartável.', 'long_description' => 'Par de protetores auriculares tipo plug para ruído em obra.'],
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Pisos e Revestimentos',
            'slug' => 'pisos-revestimentos',
            'sort_order' => 5,
            'meta_description' => 'Porcelanatos, cerâmicas, rejuntes e argamassas para acabamento.',
            'children' => [
                [
                    'nome' => 'Porcelanatos',
                    'slug' => 'pisos-porcelanatos',
                    'sort_order' => 1,
                    'products' => [
                        ['nome' => 'Porcelanato Polido 60x60 cm Off White', 'sku' => 'PR-POR-001', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 89.90, 'compare_at_price' => 99.90, 'estoque' => 200, 'short_description' => 'Porcelanato 60x60 off white polido.', 'long_description' => 'Porcelanato polido 60x60 cm off white, alto brilho para salas e dormitórios.'],
                        ['nome' => 'Porcelanato Esmaltado 90x90 cm Cinza', 'sku' => 'PR-POR-002', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 119.00, 'compare_at_price' => null, 'estoque' => 120, 'short_description' => 'Porcelanato grande formato cinza.', 'long_description' => 'Porcelanato esmaltado 90x90 cm cinza para ambientes contemporâneos.'],
                        ['nome' => 'Porcelanato Antiderrapante 45x45 cm', 'sku' => 'PR-POR-003', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 54.90, 'compare_at_price' => null, 'estoque' => 180, 'short_description' => 'Piso antiderrapante áreas externas.', 'long_description' => 'Porcelanato 45x45 antiderrapante para áreas molhadas e externas.'],
                        ['nome' => 'Porcelanato Madeira 20x120 cm', 'sku' => 'PR-POR-004', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 98.00, 'compare_at_price' => 108.00, 'estoque' => 90, 'short_description' => 'Porcelanato efeito madeira.', 'long_description' => 'Porcelanato retificado 20x120 cm efeito madeira para salas integradas.'],
                    ],
                ],
                [
                    'nome' => 'Cerâmicas',
                    'slug' => 'pisos-ceramicas',
                    'sort_order' => 2,
                    'products' => [
                        ['nome' => 'Cerâmica Piso 45x45 cm Bege', 'sku' => 'PR-CER-001', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 32.90, 'compare_at_price' => null, 'estoque' => 250, 'short_description' => 'Cerâmica piso 45x45 bege.', 'long_description' => 'Cerâmica para piso 45x45 cm bege, uso em áreas internas.'],
                        ['nome' => 'Revestimento 30x60 cm Branco Brilhante', 'sku' => 'PR-CER-002', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 38.50, 'compare_at_price' => 42.00, 'estoque' => 160, 'short_description' => 'Revestimento branco para banheiro.', 'long_description' => 'Revestimento cerâmico 30x60 branco brilhante para paredes.'],
                        ['nome' => 'Cerâmica Piso 51x51 cm Grafite', 'sku' => 'PR-CER-003', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 41.00, 'compare_at_price' => null, 'estoque' => 140, 'short_description' => 'Piso cerâmico grafite.', 'long_description' => 'Cerâmica piso 51x51 cm cor grafite para cozinhas e áreas de serviço.'],
                        ['nome' => 'Revestimento 33x45 cm Acetinado', 'sku' => 'PR-CER-004', 'brand_slug' => 'portobello', 'unit_code' => 'M2', 'preco' => 35.90, 'compare_at_price' => null, 'estoque' => 130, 'short_description' => 'Revestimento acetinado versátil.', 'long_description' => 'Revestimento 33x45 acetinado para fachadas e ambientes internos.'],
                    ],
                ],
                [
                    'nome' => 'Rejuntes',
                    'slug' => 'pisos-rejuntes',
                    'sort_order' => 3,
                    'products' => [
                        ['nome' => 'Rejunte Cerâmico Branco 1 kg', 'sku' => 'PR-REJ-001', 'brand_slug' => 'quartzolit', 'unit_code' => 'KG', 'preco' => 8.90, 'compare_at_price' => null, 'estoque' => 300, 'short_description' => 'Rejunte branco para junta até 3 mm.', 'long_description' => 'Rejunte cerâmico branco 1 kg para juntas de até 3 mm.'],
                        ['nome' => 'Rejunte Cerâmico Cinza Platina 1 kg', 'sku' => 'PR-REJ-002', 'brand_slug' => 'quartzolit', 'unit_code' => 'KG', 'preco' => 8.90, 'compare_at_price' => null, 'estoque' => 280, 'short_description' => 'Rejunte cinza platina.', 'long_description' => 'Rejunte cinza platina 1 kg para porcelanatos e cerâmicas.'],
                        ['nome' => 'Rejunte Acrílico Bege 1 kg', 'sku' => 'PR-REJ-003', 'brand_slug' => 'quartzolit', 'unit_code' => 'KG', 'preco' => 18.50, 'compare_at_price' => 21.00, 'estoque' => 90, 'short_description' => 'Rejunte acrílico flexível.', 'long_description' => 'Rejunte acrílico bege 1 kg para juntas maiores e fachadas.'],
                        ['nome' => 'Desempenadeira Borracha para Rejunte', 'sku' => 'PR-REJ-004', 'brand_slug' => 'tramontina', 'unit_code' => 'UN', 'preco' => 16.90, 'compare_at_price' => null, 'estoque' => 45, 'short_description' => 'Desempenadeira para aplicar rejunte.', 'long_description' => 'Desempenadeira de borracha para aplicação de rejunte em pisos.'],
                    ],
                ],
                [
                    'nome' => 'Argamassas Colantes',
                    'slug' => 'pisos-argamassas',
                    'sort_order' => 4,
                    'products' => [
                        ['nome' => 'Argamassa Colante Porcelanato 20 kg', 'sku' => 'PR-ARG-001', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 36.90, 'compare_at_price' => null, 'estoque' => 140, 'short_description' => 'Colante para porcelanato grande formato.', 'long_description' => 'Argamassa colante específica para porcelanato 20 kg.'],
                        ['nome' => 'Argamassa Colante Interna AC-I 20 kg', 'sku' => 'PR-ARG-002', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 22.90, 'compare_at_price' => 25.90, 'estoque' => 160, 'short_description' => 'AC-I para áreas internas secas.', 'long_description' => 'Argamassa AC-I 20 kg para assentamento interno de cerâmicas.'],
                        ['nome' => 'Argamassa Colante Externa AC-II 20 kg', 'sku' => 'PR-ARG-003', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 28.50, 'compare_at_price' => null, 'estoque' => 100, 'short_description' => 'AC-II para fachadas e áreas externas.', 'long_description' => 'Argamassa AC-II 20 kg com maior aderência para fachadas.'],
                        ['nome' => 'Regularizador de Piso 20 kg', 'sku' => 'PR-ARG-004', 'brand_slug' => 'quartzolit', 'unit_code' => 'SC', 'preco' => 26.00, 'compare_at_price' => null, 'estoque' => 75, 'short_description' => 'Massa para regularização de piso.', 'long_description' => 'Regularizador autonivelante 20 kg para preparo de contrapisos.'],
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Tintas e Acabamentos',
            'slug' => 'tintas-acabamentos',
            'sort_order' => 6,
            'meta_description' => 'Tintas, esmaltes, impermeabilizantes e massas para pintura e proteção.',
            'children' => [
                [
                    'nome' => 'Tintas para Parede',
                    'slug' => 'tintas-parede',
                    'sort_order' => 1,
                    'products' => [
                        ['nome' => 'Tinta Acrílica Fosca Branco 18 L', 'sku' => 'TN-PAR-001', 'brand_slug' => 'suvinil', 'unit_code' => 'L', 'preco' => 298.00, 'compare_at_price' => 329.00, 'estoque' => 40, 'short_description' => 'Tinta acrílica fosca 18 L branco.', 'long_description' => 'Tinta acrílica fosca branco 18 L para paredes internas e externas.'],
                        ['nome' => 'Tinta Acrílica Premium Seda Gelo 3,6 L', 'sku' => 'TN-PAR-002', 'brand_slug' => 'suvinil', 'unit_code' => 'L', 'preco' => 89.90, 'compare_at_price' => null, 'estoque' => 55, 'short_description' => 'Acabamento seda cor gelo.', 'long_description' => 'Tinta acrílica premium seda 3,6 L cor gelo para ambientes internos.'],
                        ['nome' => 'Tinta Látex PVA Branco 18 L', 'sku' => 'TN-PAR-003', 'brand_slug' => 'coral', 'unit_code' => 'L', 'preco' => 189.00, 'compare_at_price' => null, 'estoque' => 35, 'short_description' => 'Látex PVA econômico 18 L.', 'long_description' => 'Tinta látex PVA branco 18 L para áreas internas com bom custo-benefício.'],
                        ['nome' => 'Tinta Acrílica Fachada 16 L', 'sku' => 'TN-PAR-004', 'brand_slug' => 'coral', 'unit_code' => 'L', 'preco' => 349.00, 'compare_at_price' => 379.00, 'estoque' => 22, 'short_description' => 'Tinta para fachada impermeabilizante.', 'long_description' => 'Tinta acrílica para fachada 16 L com proteção contra chuva e UV.'],
                    ],
                ],
                [
                    'nome' => 'Esmaltes',
                    'slug' => 'tintas-esmaltes',
                    'sort_order' => 2,
                    'products' => [
                        ['nome' => 'Esmalte Sintético Branco Brilho 3,6 L', 'sku' => 'TN-ESM-001', 'brand_slug' => 'coral', 'unit_code' => 'L', 'preco' => 124.00, 'compare_at_price' => null, 'estoque' => 30, 'short_description' => 'Esmalte branco brilhante para metal e madeira.', 'long_description' => 'Esmalte sintético branco brilho 3,6 L para portões, grades e móveis.'],
                        ['nome' => 'Esmalte Sintético Preto Fosco 900 ml', 'sku' => 'TN-ESM-002', 'brand_slug' => 'suvinil', 'unit_code' => 'L', 'preco' => 42.90, 'compare_at_price' => 48.00, 'estoque' => 50, 'short_description' => 'Esmalte preto fosco.', 'long_description' => 'Esmalte sintético preto fosco 900 ml para estruturas metálicas.'],
                        ['nome' => 'Tinta Spray Preto Fosco 400 ml', 'sku' => 'TN-ESM-003', 'brand_slug' => 'suvinil', 'unit_code' => 'UN', 'preco' => 24.90, 'compare_at_price' => null, 'estoque' => 80, 'short_description' => 'Spray para retoques rápidos.', 'long_description' => 'Tinta spray preto fosco 400 ml para manutenção e pequenos reparos.'],
                        ['nome' => 'Esmalte Base Água Branco 3,2 L', 'sku' => 'TN-ESM-004', 'brand_slug' => 'coral', 'unit_code' => 'L', 'preco' => 98.00, 'compare_at_price' => null, 'estoque' => 28, 'short_description' => 'Esmalte à base de água baixo odor.', 'long_description' => 'Esmalte base água branco 3,2 L baixo odor para ambientes internos.'],
                    ],
                ],
                [
                    'nome' => 'Impermeabilizantes',
                    'slug' => 'tintas-impermeabilizantes',
                    'sort_order' => 3,
                    'products' => [
                        ['nome' => 'Impermeabilizante Acrílico 18 kg', 'sku' => 'TN-IMP-001', 'brand_slug' => 'quartzolit', 'unit_code' => 'KG', 'preco' => 289.00, 'compare_at_price' => 310.00, 'estoque' => 25, 'short_description' => 'Impermeabilizante para lajes e telhados.', 'long_description' => 'Impermeabilizante acrílico 18 kg flexível para lajes expostas.'],
                        ['nome' => 'Manta Líquida Poliuretano 15 kg', 'sku' => 'TN-IMP-002', 'brand_slug' => 'quartzolit', 'unit_code' => 'KG', 'preco' => 420.00, 'compare_at_price' => null, 'estoque' => 12, 'short_description' => 'Manta líquida PU para áreas críticas.', 'long_description' => 'Manta líquida poliuretano 15 kg para lajes, piscinas e reservatórios.'],
                        ['nome' => 'Aditivo Impermeabilizante 1 L', 'sku' => 'TN-IMP-003', 'brand_slug' => 'quartzolit', 'unit_code' => 'L', 'preco' => 34.90, 'compare_at_price' => null, 'estoque' => 60, 'short_description' => 'Aditivo para argamassa e concreto.', 'long_description' => 'Aditivo impermeabilizante 1 L para misturas de concreto e argamassa.'],
                        ['nome' => 'Selador Acrílico 5 L', 'sku' => 'TN-IMP-004', 'brand_slug' => 'suvinil', 'unit_code' => 'L', 'preco' => 89.00, 'compare_at_price' => null, 'estoque' => 32, 'short_description' => 'Selador preparador de superfícies.', 'long_description' => 'Selador acrílico 5 L para preparação de paredes antes da pintura.'],
                    ],
                ],
                [
                    'nome' => 'Massas',
                    'slug' => 'tintas-massas',
                    'sort_order' => 4,
                    'products' => [
                        ['nome' => 'Massa Corrida PVA 25 kg', 'sku' => 'TN-MAS-001', 'brand_slug' => 'suvinil', 'unit_code' => 'KG', 'preco' => 62.00, 'compare_at_price' => 68.00, 'estoque' => 70, 'short_description' => 'Massa corrida interna 25 kg.', 'long_description' => 'Massa corrida PVA 25 kg para acabamento de paredes internas.'],
                        ['nome' => 'Massa Acrílica 25 kg', 'sku' => 'TN-MAS-002', 'brand_slug' => 'coral', 'unit_code' => 'KG', 'preco' => 78.00, 'compare_at_price' => null, 'estoque' => 50, 'short_description' => 'Massa acrílica para áreas externas.', 'long_description' => 'Massa acrílica 25 kg resistente para fachadas e áreas externas.'],
                        ['nome' => 'Massa Niveladora 1,4 kg', 'sku' => 'TN-MAS-003', 'brand_slug' => 'quartzolit', 'unit_code' => 'KG', 'preco' => 18.90, 'compare_at_price' => null, 'estoque' => 90, 'short_description' => 'Massa para correção de trincas.', 'long_description' => 'Massa niveladora 1,4 kg para reparos pontuais antes da pintura.'],
                        ['nome' => 'Textura Acrílica Rústica 25 kg', 'sku' => 'TN-MAS-004', 'brand_slug' => 'suvinil', 'unit_code' => 'KG', 'preco' => 95.00, 'compare_at_price' => 105.00, 'estoque' => 24, 'short_description' => 'Textura rústica para fachadas.', 'long_description' => 'Textura acrílica rústica 25 kg para acabamento decorativo de fachadas.'],
                    ],
                ],
            ],
        ],
    ],
];

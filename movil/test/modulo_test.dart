import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:innovasteam/api/cliente.dart';
import 'package:innovasteam/pantallas/modulo.dart';
import 'package:innovasteam/pantallas/asistencia.dart';

/// Respuestas de ejemplo con la forma exacta que devuelve
/// api/movil.php. Si el servidor cambia el contrato, estos tests
/// dejan de reflejar la realidad: hay que actualizarlos a la vez.
const _moduloJson = {
  'ok': true,
  'modulo': {
    'id': 1,
    'titulo': 'El mercado de Moquegua',
    'descripcion': 'Precios y gráficos',
    'curso': 'Matemática',
    'color': '#f5c842',
    'minutos': 45,
  },
  'pasos': [
    {
      'numero': 1,
      'tipo': 'historia',
      'contenido': {
        'narrativa': 'Don Aurelio vende papas en el mercado central.',
        'pregunta_disparadora': '¿Cómo sabrías si su precio es justo?',
        'conceptos_clave': ['gráfico de barras'],
      },
    },
    {
      'numero': 2,
      'tipo': 'actividad',
      'contenido': {
        'materiales': ['Cuaderno', 'Regla'],
        'instrucciones': ['Anota cinco precios.', 'Dibuja las barras.'],
        'minutos': 20,
      },
    },
    {
      'numero': 3,
      'tipo': 'quiz',
      'contenido': {'info': 'ver quiz_preguntas'},
      'preguntas': [
        {
          'id': 7,
          'texto': '¿Qué es un gráfico de barras?',
          'opciones': ['Una tabla', 'Una representación visual', 'Una calculadora'],
        },
      ],
    },
    {
      'numero': 4,
      'tipo': 'entregable',
      'contenido': {
        'consigna': 'Entrega tu gráfico terminado.',
        'formatos': ['ficha', 'dibujo_cientifico'],
        'instrucciones': 'Sube una foto.',
      },
    },
  ],
  'progreso': {'paso_actual': 1, 'completado': false, 'estrellas': 0},
};

const _aulasJson = {
  'ok': true,
  'aulas': [
    {
      'id': 1,
      'nombre': '5to de primaria "A"',
      'estudiantes': [
        {'id': 5, 'nombre': 'Mamani, Sofía'},
        {'id': 6, 'nombre': 'Flores, Marco'},
      ],
    },
  ],
  'modulos': [
    {'id': 1, 'titulo': 'El mercado de Moquegua', 'curso': 'Matemática'},
  ],
};

/// Cliente con un servidor simulado. Registra qué se envió para poder
/// comprobar que la pantalla manda lo correcto, no solo que no revienta.
class _Espia {
  final List<String> llamadas = [];
  final List<Map<String, dynamic>> cuerpos = [];

  ClienteApi construir() {
    final falso = MockClient((req) async {
      llamadas.add('${req.method} ${req.url.path}${req.url.query.isEmpty ? '' : '?${req.url.query}'}');
      if (req is http.Request && req.body.isNotEmpty) {
        try {
          cuerpos.add(jsonDecode(req.body) as Map<String, dynamic>);
        } catch (_) {/* multipart u otro formato */}
      }

      if (req.url.query.contains('recurso=modulo')) {
        return http.Response(jsonEncode(_moduloJson), 200,
            headers: {'content-type': 'application/json'});
      }
      if (req.url.query.contains('recurso=aulas')) {
        return http.Response(jsonEncode(_aulasJson), 200,
            headers: {'content-type': 'application/json'});
      }
      if (req.url.path.endsWith('quiz.php')) {
        return http.Response(
            jsonEncode({'ok': true, 'correctas': 1, 'total': 1, 'estrellas': 3}), 200,
            headers: {'content-type': 'application/json'});
      }
      if (req.url.path.endsWith('progreso.php')) {
        return http.Response(
            jsonEncode({'ok': true, 'siguiente_paso': 2, 'completado': false}), 200,
            headers: {'content-type': 'application/json'});
      }
      if (req.url.path.endsWith('asistencia.php')) {
        return http.Response(
            jsonEncode({'ok': true, 'sesion_id': 9, 'asistentes': 2}), 200,
            headers: {'content-type': 'application/json'});
      }
      return http.Response(jsonEncode({'ok': false, 'error': 'ruta no simulada'}), 404,
          headers: {'content-type': 'application/json'});
    });

    return ClienteApi(baseUrl: 'http://prueba.local', cliente: falso);
  }
}

void main() {
  setUp(() {
    // El cliente guarda el token con SharedPreferences.
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('el módulo recorre los cuatro pasos y envía el quiz', (tester) async {
    final espia = _Espia();
    final api = espia.construir();

    await tester.pumpWidget(MaterialApp(
      home: PantallaModulo(api: api, moduloId: 1, titulo: 'El mercado de Moquegua'),
    ));
    await tester.pumpAndSettle();

    // Paso 1: la historia, con su pregunta para pensar.
    expect(find.textContaining('Don Aurelio vende papas'), findsOneWidget);
    expect(find.textContaining('precio es justo'), findsOneWidget);

    await tester.tap(find.text('Continuar'));
    await tester.pumpAndSettle();

    // Paso 2: la actividad, con materiales e instrucciones numeradas.
    expect(find.text('Qué necesitas'), findsOneWidget);
    expect(find.textContaining('Anota cinco precios'), findsOneWidget);

    await tester.tap(find.text('Continuar'));
    await tester.pumpAndSettle();

    // Paso 3: el quiz. Sin responder, no deja enviar.
    expect(find.textContaining('¿Qué es un gráfico de barras?'), findsOneWidget);

    await tester.tap(find.text('Una representación visual'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Enviar respuestas'));
    await tester.pumpAndSettle();

    expect(find.textContaining('1 de 1 correctas'), findsOneWidget);

    // Se envió lo que corresponde, con la opción elegida por índice.
    final envio = espia.cuerpos.firstWhere((c) => c.containsKey('respuestas'));
    expect(envio['modulo_id'], 1);
    expect((envio['respuestas'] as List).first, {'pregunta_id': 7, 'opcion_elegida': 1});

    await tester.tap(find.text('Continuar al entregable'));
    await tester.pumpAndSettle();

    // Paso 4: el entregable. Sin foto, el botón de entregar está inerte.
    expect(find.textContaining('Entrega tu gráfico'), findsOneWidget);
    final boton = tester.widget<FilledButton>(
      find.widgetWithText(FilledButton, 'Entregar y completar'),
    );
    expect(boton.onPressed, isNull,
        reason: 'sin foto no debe poderse entregar');
  });

  testWidgets('el quiz no se puede saltar sin responderlo', (tester) async {
    final api = _Espia().construir();

    await tester.pumpWidget(MaterialApp(
      home: PantallaModulo(api: api, moduloId: 1, titulo: 'x'),
    ));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Continuar'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Continuar'));
    await tester.pumpAndSettle();

    final boton = tester.widget<FilledButton>(
      find.widgetWithText(FilledButton, 'Continuar al entregable'),
    );
    expect(boton.onPressed, isNull);
  });

  testWidgets('la asistencia marca a todos y registra la sesión', (tester) async {
    final espia = _Espia();
    final api = espia.construir();

    await tester.pumpWidget(MaterialApp(
      home: PantallaAsistencia(
        api: api,
        usuario: const Usuario(
            id: 4, nombre: 'Jorge', apellido: 'Quispe', rol: 'practicante'),
      ),
    ));
    await tester.pumpAndSettle();

    // Arranca con todos presentes: en un aula normal faltan pocos.
    expect(find.text('2 de 2 presentes'), findsOneWidget);

    // Desmarcar a uno se refleja en el contador.
    await tester.tap(find.text('Flores, Marco'));
    await tester.pumpAndSettle();
    expect(find.text('1 de 2 presentes'), findsOneWidget);

    await tester.tap(find.text('Registrar sesión'));
    await tester.pumpAndSettle();

    final envio = espia.cuerpos.firstWhere((c) => c.containsKey('asistentes'));
    expect(envio['aula_id'], 1);
    expect(envio['modulo_id'], 1);
    expect(envio['asistentes'], [5]);
    // La fecha va en el formato que el servidor valida.
    expect(envio['fecha_sesion'], matches(r'^\d{4}-\d{2}-\d{2}$'));
  });
}

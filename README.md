# Stellar — Educación STEAM & Web

Plataforma educativa moderna para aprender **Ciencias, Tecnología, Ingeniería, Arte, Matemáticas y Desarrollo Web**.

## Stack tecnológico

- **Framework:** Next.js 14 (App Router, Static Export)
- **Lenguaje:** TypeScript
- **Estilos:** Tailwind CSS
- **Iconos:** Lucide React
- **Tema:** Space / Stellar con animaciones CSS

## Estructura del proyecto

```
src/
├── app/
│   ├── layout.tsx          # Layout raíz (Navbar + Starfield)
│   ├── page.tsx            # Landing page
│   ├── globals.css         # Estilos globales + utilidades
│   ├── courses/
│   │   └── page.tsx        # Catálogo de cursos con filtros
│   └── about/
│       └── page.tsx        # Página nosotros
├── components/
│   ├── Navbar.tsx          # Navegación responsiva
│   ├── Starfield.tsx       # Canvas animado de estrellas
│   ├── Hero.tsx            # Sección principal
│   ├── Features.tsx        # Características de la plataforma
│   ├── CoursesPreview.tsx  # Preview de cursos destacados
│   ├── Testimonials.tsx    # Testimonios de estudiantes
│   ├── Contact.tsx         # Formulario de contacto
│   └── Footer.tsx          # Pie de página
└── data/
    └── courses.ts          # Catálogo de 12 cursos STEAM + Web
```

## Páginas

| Ruta         | Descripción                          |
|--------------|--------------------------------------|
| `/`          | Landing page completa                |
| `/courses`   | Catálogo con búsqueda y filtros      |
| `/about`     | Historia, misión, valores y equipo   |

## Desarrollo local

```bash
npm install
npm run dev
```

Abre [http://localhost:3000](http://localhost:3000)

## Build

```bash
npm run build
```

Genera el sitio estático en `/out`.

---

**Stellar** — *Aprende. Crea. Innova.*

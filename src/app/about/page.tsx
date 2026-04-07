import { Rocket, Target, Heart, Users } from 'lucide-react'
import Footer from '@/components/Footer'

const values = [
  {
    icon: Rocket,
    color: 'text-purple-400',
    bg: 'bg-purple-500/10',
    title: 'Innovación',
    description: 'Adoptamos las últimas tecnologías y metodologías para ofrecer una experiencia educativa de vanguardia.',
  },
  {
    icon: Heart,
    color: 'text-pink-400',
    bg: 'bg-pink-500/10',
    title: 'Pasión',
    description: 'Creemos que el aprendizaje ocurre cuando hay pasión. Por eso diseñamos cada curso con dedicación.',
  },
  {
    icon: Users,
    color: 'text-blue-400',
    bg: 'bg-blue-500/10',
    title: 'Comunidad',
    description: 'No aprendes solo. Nuestra comunidad te rodea de compañeros, mentores y colaboradores.',
  },
  {
    icon: Target,
    color: 'text-cyan-400',
    bg: 'bg-cyan-500/10',
    title: 'Impacto',
    description: 'Medimos nuestro éxito por el progreso real de nuestros estudiantes, no solo por las matrículas.',
  },
]

const team = [
  {
    name: 'Andrés Sotomayor',
    role: 'Fundador & CEO',
    avatar: 'AS',
    gradient: 'from-purple-500 to-blue-500',
    bio: 'Ingeniero de software con 10 años de experiencia en EdTech. Apasionado por democratizar el acceso a la educación tech.',
  },
  {
    name: 'María Fernández',
    role: 'Directora de Contenido',
    avatar: 'MF',
    gradient: 'from-pink-500 to-rose-500',
    bio: 'Doctora en Ciencias de la Educación. Ha diseñado programas curriculares para universidades de toda Latinoamérica.',
  },
  {
    name: 'Carlos Mendoza',
    role: 'Lead Engineer',
    avatar: 'CM',
    gradient: 'from-cyan-500 to-teal-500',
    bio: 'Full-stack developer y educador. Ha formado a más de 500 desarrolladores junior en su carrera docente.',
  },
  {
    name: 'Laura Jiménez',
    role: 'Diseñadora UX/UI',
    avatar: 'LJ',
    gradient: 'from-yellow-500 to-orange-500',
    bio: 'Diseñadora de experiencias de usuario con enfoque en accesibilidad e interfaces educativas inclusivas.',
  },
]

const milestones = [
  { year: '2023', event: 'Fundación de Stellar con 5 cursos piloto' },
  { year: '2024', event: 'Lanzamiento oficial con 500 estudiantes inscritos' },
  { year: '2025', event: 'Expansión a 12 países y 50 cursos disponibles' },
  { year: '2026', event: '1,200+ estudiantes activos y 95% de satisfacción' },
]

export default function AboutPage() {
  return (
    <>
      <div className="min-h-screen px-4 pt-28 pb-16">
        {/* Hero */}
        <div className="max-w-4xl mx-auto text-center mb-24">
          <div className="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none" />
          <p className="text-sm font-semibold text-purple-400 uppercase tracking-widest mb-4">
            Nuestra historia
          </p>
          <h1 className="text-5xl sm:text-6xl font-extrabold text-white mb-6 leading-tight">
            Educación que{' '}
            <span className="gradient-text">trasciende</span>
          </h1>
          <p className="text-white/55 text-lg leading-relaxed max-w-2xl mx-auto">
            Stellar nació de una convicción simple: <strong className="text-white/80">el acceso a educación de
            calidad no debe ser un privilegio</strong>. Somos un equipo de educadores, ingenieros y
            creativos comprometidos con transformar el aprendizaje en Latinoamérica.
          </p>
        </div>

        <div className="max-w-7xl mx-auto">
          {/* Mission & Vision */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-24">
            <div className="glass rounded-2xl p-8 border border-purple-500/20">
              <div className="inline-flex p-3 rounded-xl bg-purple-500/10 mb-5">
                <Target size={24} className="text-purple-400" />
              </div>
              <h2 className="text-2xl font-bold text-white mb-3">Misión</h2>
              <p className="text-white/55 leading-relaxed">
                Proporcionar educación STEAM y tecnológica de excelencia, accesible para todos,
                a través de una plataforma digital que combina rigor académico con experiencias
                de aprendizaje inmersivas e interactivas.
              </p>
            </div>
            <div className="glass rounded-2xl p-8 border border-cyan-500/20">
              <div className="inline-flex p-3 rounded-xl bg-cyan-500/10 mb-5">
                <Rocket size={24} className="text-cyan-400" />
              </div>
              <h2 className="text-2xl font-bold text-white mb-3">Visión</h2>
              <p className="text-white/55 leading-relaxed">
                Ser la plataforma educativa de referencia en Latinoamérica, formando la próxima
                generación de científicos, ingenieros, artistas y desarrolladores web que impulsen
                el desarrollo regional.
              </p>
            </div>
          </div>

          {/* Values */}
          <div className="mb-24">
            <div className="text-center mb-12">
              <h2 className="text-4xl font-bold text-white mb-3">
                Nuestros <span className="gradient-text">valores</span>
              </h2>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {values.map(({ icon: Icon, color, bg, title, description }) => (
                <div key={title} className="glass rounded-2xl p-6 card-hover border border-white/5 text-center">
                  <div className={`inline-flex p-3 rounded-xl ${bg} mb-4`}>
                    <Icon size={22} className={color} />
                  </div>
                  <h3 className="text-white font-semibold mb-2">{title}</h3>
                  <p className="text-white/45 text-sm leading-relaxed">{description}</p>
                </div>
              ))}
            </div>
          </div>

          {/* Timeline */}
          <div className="mb-24">
            <div className="text-center mb-12">
              <h2 className="text-4xl font-bold text-white mb-3">
                Nuestra <span className="gradient-text">trayectoria</span>
              </h2>
            </div>
            <div className="max-w-2xl mx-auto">
              {milestones.map(({ year, event }, i) => (
                <div key={year} className="flex gap-6 mb-8 last:mb-0">
                  <div className="flex flex-col items-center">
                    <div className="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                      {year.slice(2)}
                    </div>
                    {i < milestones.length - 1 && (
                      <div className="w-px flex-1 mt-2 bg-gradient-to-b from-purple-500/40 to-transparent" />
                    )}
                  </div>
                  <div className="pt-2 pb-8 last:pb-0">
                    <span className="text-xs text-purple-400 font-semibold tracking-widest">{year}</span>
                    <p className="text-white/70 text-sm mt-1 leading-relaxed">{event}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Team */}
          <div>
            <div className="text-center mb-12">
              <h2 className="text-4xl font-bold text-white mb-3">
                El equipo <span className="gradient-text">detrás de Stellar</span>
              </h2>
              <p className="text-white/45 max-w-lg mx-auto">
                Somos un equipo diverso y apasionado, unidos por la misión de transformar la educación.
              </p>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {team.map(({ name, role, avatar, gradient, bio }) => (
                <div key={name} className="glass rounded-2xl p-6 card-hover border border-white/5 text-center">
                  <div
                    className={`w-16 h-16 rounded-2xl bg-gradient-to-br ${gradient} flex items-center justify-center text-white font-bold text-lg mx-auto mb-4`}
                  >
                    {avatar}
                  </div>
                  <h3 className="text-white font-semibold text-sm">{name}</h3>
                  <p className="text-purple-400 text-xs mt-1 mb-3">{role}</p>
                  <p className="text-white/40 text-xs leading-relaxed">{bio}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </>
  )
}

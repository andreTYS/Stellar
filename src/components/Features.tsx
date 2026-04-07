import { Zap, Globe, Brain, Trophy, Users, Rocket } from 'lucide-react'

const features = [
  {
    icon: Brain,
    color: 'text-purple-400',
    glow: 'bg-purple-500/10',
    title: 'Aprendizaje Adaptativo',
    description:
      'Nuestro sistema se ajusta a tu ritmo y estilo de aprendizaje, entregándote el contenido ideal en el momento preciso.',
  },
  {
    icon: Globe,
    color: 'text-blue-400',
    glow: 'bg-blue-500/10',
    title: 'Contenido Multilingüe',
    description:
      'Accede a materiales en español e inglés. Aprende en tu idioma y expande horizontes al mismo tiempo.',
  },
  {
    icon: Zap,
    color: 'text-yellow-400',
    glow: 'bg-yellow-500/10',
    title: 'Proyectos Prácticos',
    description:
      'Cada curso incluye proyectos reales para aplicar lo aprendido. Construye tu portafolio mientras estudias.',
  },
  {
    icon: Trophy,
    color: 'text-cyan-400',
    glow: 'bg-cyan-500/10',
    title: 'Certificaciones',
    description:
      'Obtén certificados reconocidos al completar cada módulo. Valida tus conocimientos ante empleadores.',
  },
  {
    icon: Users,
    color: 'text-pink-400',
    glow: 'bg-pink-500/10',
    title: 'Comunidad Activa',
    description:
      'Únete a una comunidad de estudiantes y mentores. Colabora, aprende y crece junto a otros apasionados.',
  },
  {
    icon: Rocket,
    color: 'text-green-400',
    glow: 'bg-green-500/10',
    title: 'Rutas de Carrera',
    description:
      'Sigue rutas de aprendizaje estructuradas para roles específicos en tecnología, ciencias y artes digitales.',
  },
]

export default function Features() {
  return (
    <section id="features" className="relative py-24 px-4">
      {/* Section glow */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-px h-32 bg-gradient-to-b from-transparent via-purple-500/50 to-transparent" />

      <div className="max-w-7xl mx-auto">
        {/* Heading */}
        <div className="text-center mb-16">
          <p className="text-sm font-semibold text-purple-400 uppercase tracking-widest mb-3">
            Por qué Stellar
          </p>
          <h2 className="text-4xl sm:text-5xl font-bold text-white mb-4">
            Todo lo que necesitas{' '}
            <span className="gradient-text">en un solo lugar</span>
          </h2>
          <p className="max-w-xl mx-auto text-white/50 text-lg">
            Herramientas de última generación para transformar tu experiencia educativa.
          </p>
        </div>

        {/* Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {features.map(({ icon: Icon, color, glow, title, description }) => (
            <div
              key={title}
              className="glass rounded-2xl p-6 card-hover group border border-white/5 hover:border-white/10"
            >
              <div className={`inline-flex p-3 rounded-xl ${glow} mb-4 transition-transform duration-300 group-hover:scale-110`}>
                <Icon size={24} className={color} />
              </div>
              <h3 className="text-lg font-semibold text-white mb-2">{title}</h3>
              <p className="text-white/50 text-sm leading-relaxed">{description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

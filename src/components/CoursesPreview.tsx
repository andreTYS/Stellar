import Link from 'next/link'
import { ArrowRight, Clock, Star } from 'lucide-react'
import { STEAM_COURSES } from '@/data/courses'

export default function CoursesPreview() {
  const featured = STEAM_COURSES.slice(0, 6)

  return (
    <section id="courses" className="relative py-24 px-4">
      {/* Glow */}
      <div className="absolute bottom-0 right-0 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none" />

      <div className="max-w-7xl mx-auto">
        {/* Heading */}
        <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-12">
          <div>
            <p className="text-sm font-semibold text-cyan-400 uppercase tracking-widest mb-3">
              Nuestros cursos
            </p>
            <h2 className="text-4xl sm:text-5xl font-bold text-white">
              Aprende con{' '}
              <span className="gradient-text">los mejores</span>
            </h2>
          </div>
          <Link href="/courses" className="btn-secondary text-sm whitespace-nowrap self-start sm:self-auto">
            Ver todos
            <ArrowRight size={16} />
          </Link>
        </div>

        {/* Course grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {featured.map((course) => (
            <Link
              key={course.id}
              href={`/courses`}
              className="glass rounded-2xl overflow-hidden card-hover group border border-white/5 hover:border-white/10 block"
            >
              {/* Card header with gradient */}
              <div
                className={`h-36 bg-gradient-to-br ${course.gradient} flex items-center justify-center relative overflow-hidden`}
              >
                <span className="text-5xl group-hover:scale-110 transition-transform duration-300 select-none">
                  {course.emoji}
                </span>
                <div className="absolute inset-0 bg-black/20" />
                <span className="absolute top-3 right-3 text-xs px-2 py-1 rounded-full bg-black/40 text-white/80 backdrop-blur-sm">
                  {course.level}
                </span>
              </div>

              {/* Card body */}
              <div className="p-5">
                <div className="flex items-center gap-2 mb-2">
                  <span
                    className="text-xs px-2 py-0.5 rounded-full font-medium"
                    style={{ background: course.tagBg, color: course.tagColor }}
                  >
                    {course.tag}
                  </span>
                </div>
                <h3 className="font-semibold text-white text-base mb-2 leading-snug">
                  {course.title}
                </h3>
                <p className="text-white/50 text-sm leading-relaxed line-clamp-2 mb-4">
                  {course.description}
                </p>

                <div className="flex items-center justify-between text-xs text-white/40">
                  <span className="flex items-center gap-1">
                    <Clock size={12} /> {course.duration}
                  </span>
                  <span className="flex items-center gap-1">
                    <Star size={12} className="text-yellow-400 fill-yellow-400" />
                    {course.rating}
                  </span>
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  )
}

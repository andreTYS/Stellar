'use client'

import { useState } from 'react'
import { Clock, Star, BookOpen, Search } from 'lucide-react'
import Footer from '@/components/Footer'
import { STEAM_COURSES, CATEGORIES } from '@/data/courses'

export default function CoursesPage() {
  const [activeCategory, setActiveCategory] = useState('all')
  const [query, setQuery] = useState('')

  const filtered = STEAM_COURSES.filter((c) => {
    const matchCat = activeCategory === 'all' || c.category === activeCategory
    const matchQ =
      query === '' ||
      c.title.toLowerCase().includes(query.toLowerCase()) ||
      c.description.toLowerCase().includes(query.toLowerCase())
    return matchCat && matchQ
  })

  return (
    <>
      <div className="min-h-screen px-4 pt-28 pb-16">
        {/* Header */}
        <div className="max-w-7xl mx-auto text-center mb-14">
          <div className="absolute top-1/3 left-1/2 -translate-x-1/2 w-96 h-64 bg-purple-600/10 rounded-full blur-3xl pointer-events-none" />
          <p className="text-sm font-semibold text-purple-400 uppercase tracking-widest mb-3">
            Catálogo completo
          </p>
          <h1 className="text-5xl sm:text-6xl font-extrabold text-white mb-4">
            Todos los{' '}
            <span className="gradient-text">cursos</span>
          </h1>
          <p className="text-white/50 text-lg max-w-xl mx-auto">
            {STEAM_COURSES.length} cursos disponibles en Ciencias, Tecnología, Ingeniería, Arte,
            Matemáticas y Desarrollo Web.
          </p>

          {/* Search */}
          <div className="relative max-w-md mx-auto mt-8">
            <Search size={16} className="absolute left-4 top-1/2 -translate-y-1/2 text-white/30" />
            <input
              type="search"
              placeholder="Buscar cursos..."
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              className="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-sm text-white placeholder-white/30 focus:outline-none focus:border-purple-500/50 transition-colors"
            />
          </div>
        </div>

        <div className="max-w-7xl mx-auto">
          {/* Category tabs */}
          <div className="flex flex-wrap gap-2 mb-10 justify-center">
            {CATEGORIES.map(({ id, label, emoji }) => (
              <button
                key={id}
                onClick={() => setActiveCategory(id)}
                className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 ${
                  activeCategory === id
                    ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg shadow-purple-500/20'
                    : 'glass text-white/60 hover:text-white border border-white/5 hover:border-white/15'
                }`}
              >
                <span>{emoji}</span>
                {label}
              </button>
            ))}
          </div>

          {/* Results count */}
          <p className="text-white/30 text-sm mb-6 text-center">
            {filtered.length} curso{filtered.length !== 1 ? 's' : ''} encontrado{filtered.length !== 1 ? 's' : ''}
          </p>

          {/* Grid */}
          {filtered.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {filtered.map((course) => (
                <div
                  key={course.id}
                  className="glass rounded-2xl overflow-hidden card-hover group border border-white/5 hover:border-white/10 flex flex-col"
                >
                  {/* Image */}
                  <div
                    className={`h-32 bg-gradient-to-br ${course.gradient} flex items-center justify-center relative overflow-hidden`}
                  >
                    <span className="text-4xl group-hover:scale-110 transition-transform duration-300 select-none">
                      {course.emoji}
                    </span>
                    <div className="absolute inset-0 bg-black/20" />
                    <span className="absolute top-2 right-2 text-xs px-2 py-0.5 rounded-full bg-black/40 text-white/80 backdrop-blur-sm">
                      {course.level}
                    </span>
                  </div>

                  {/* Body */}
                  <div className="p-4 flex flex-col flex-1">
                    <span
                      className="inline-block text-xs px-2 py-0.5 rounded-full font-medium mb-2 self-start"
                      style={{ background: course.tagBg, color: course.tagColor }}
                    >
                      {course.tag}
                    </span>
                    <h3 className="font-semibold text-white text-sm mb-2 leading-snug">{course.title}</h3>
                    <p className="text-white/40 text-xs leading-relaxed line-clamp-2 mb-3 flex-1">
                      {course.description}
                    </p>

                    <div className="flex items-center justify-between text-xs text-white/40 mb-4">
                      <span className="flex items-center gap-1">
                        <Clock size={11} /> {course.duration}
                      </span>
                      <span className="flex items-center gap-1">
                        <BookOpen size={11} /> {course.modules} módulos
                      </span>
                      <span className="flex items-center gap-1">
                        <Star size={11} className="text-yellow-400 fill-yellow-400" /> {course.rating}
                      </span>
                    </div>

                    <button className="w-full py-2 rounded-xl text-xs font-semibold bg-gradient-to-r from-purple-600/30 to-blue-600/30 border border-purple-500/20 text-purple-300 hover:from-purple-600/50 hover:to-blue-600/50 hover:text-white transition-all duration-200">
                      Ver curso
                    </button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-20">
              <p className="text-5xl mb-4">🌌</p>
              <p className="text-white/40 text-lg">No encontramos cursos con ese criterio.</p>
              <button
                onClick={() => { setQuery(''); setActiveCategory('all') }}
                className="mt-4 text-purple-400 text-sm hover:text-purple-300 underline transition-colors"
              >
                Limpiar filtros
              </button>
            </div>
          )}
        </div>
      </div>
      <Footer />
    </>
  )
}
